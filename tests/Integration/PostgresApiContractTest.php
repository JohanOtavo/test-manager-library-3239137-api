<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Adapters\Persistence\Database\DatabaseBookRepository;
use App\Adapters\Security\NativePasswordHasher;
use App\Application\Port\TokenIssuer;
use App\Domain\Book\Book;
use App\Domain\Shared\Isbn;
use App\Domain\Shared\Quantity;
use App\Domain\Shared\Role;
use App\Domain\User\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Runs against PostgreSQL because the production migrations use PostgreSQL schemas and partial indexes. */
final class PostgresApiContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'Integration tests require PostgreSQL.');
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_health_is_public_and_protected_routes_return_the_shared_401_body(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);
        $this->getJson('/api/v1/categories')->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonStructure(['code', 'message', 'details']);
    }

    public function test_admin_can_log_in_create_a_librarian_and_create_a_book(): void
    {
        $adminPassword = 'Admin-password-2026';
        $this->app['config']->set('library.admin_username', 'admin');
        $this->app['config']->set('library.admin_password', $adminPassword);
        $this->artisan('library:ensure-admin')->assertExitCode(0);
        $this->artisan('library:ensure-admin')->assertExitCode(0);
        $this->assertSame(1, DB::table('library.user')->where('username', 'admin')->count());

        $login = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => $adminPassword,
        ])->assertOk()->assertJsonPath('role', 'admin')->assertJsonPath('token_type', 'Bearer');
        $token = $login->json('access_token');
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ])->assertUnauthorized()->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $this->postJson('/api/v1/users', [
            'username' => 'librarian-1',
            'password' => 'Librarian-password-1',
            'role' => 'librarian',
        ], $headers)->assertCreated()->assertJsonPath('role', 'librarian');

        $createdBook = $this->postJson('/api/v1/books', [
            'title' => 'Libro de integración',
            'author' => 'Equipo de pruebas',
            'isbn' => '9780306406157',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_copies' => 2,
        ], $headers)->assertCreated()->assertJsonPath('available_copies', 2);
        $bookId = $createdBook->json('id');
        $this->getJson('/api/v1/books/'.$bookId, $headers)->assertOk()->assertJsonPath('title', 'Libro de integración');
        $this->getJson('/api/v1/books?q=integración', $headers)->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/reports/most-borrowed?from=2026-01-01&to=2026-01-31', $headers)
            ->assertOk()->assertJsonPath('rows', []);
        $this->getJson('/api/v1/reports/most-borrowed?from=2026-02-01&to=2026-01-31', $headers)
            ->assertUnprocessable()->assertJsonPath('code', 'INVALID_DATE_RANGE');
        $this->deleteJson('/api/v1/books/'.$bookId, [], $headers)->assertNoContent();
        $this->getJson('/api/v1/books/'.$bookId, $headers)->assertNotFound()->assertJsonPath('code', 'NOT_FOUND');

        $this->postJson('/api/v1/users', [
            'username' => 'not-admin',
            'password' => 'Librarian-password-2',
            'role' => 'admin',
        ], $headers)->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_librarian_cannot_use_admin_routes_and_compare_and_set_rejects_stale_withdrawal(): void
    {
        $passwords = new NativePasswordHasher();
        $userId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        DB::table('library.user')->insert([
            'id' => $userId,
            'username' => 'librarian',
            'password_hash' => $passwords->hash('Librarian-password-1'),
            'role' => Role::Librarian->value,
        ]);
        $token = app(TokenIssuer::class)->issue($userId, Role::Librarian)->accessToken;
        $headers = ['Authorization' => 'Bearer '.$token];
        $this->postJson('/api/v1/books', [], $headers)->assertForbidden()->assertJsonPath('code', 'FORBIDDEN_ROLE');
        $this->getJson('/api/v1/reports/most-borrowed?from=2026-01-01&to=2026-01-31', $headers)
            ->assertForbidden()->assertJsonPath('code', 'FORBIDDEN_ROLE');

        $book = Book::register(
            'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'Último ejemplar',
            'Autora',
            Isbn::fromString('9780306406157'),
            '11111111-1111-4111-8111-111111111111',
            Quantity::fromInt(1),
        );
        $books = app(DatabaseBookRepository::class);
        $books->add($book);
        $this->assertTrue($books->applyWithdrawal($book->withdraw(), 1));
        // Simulates a concurrent request that read the same available-copy value before the winner committed.
        $this->assertFalse($books->applyWithdrawal($book->withdraw(), 1));
        $this->assertSame(0, DB::table('library.book')->where('id', $book->id)->value('available_copies'));
    }

    public function test_two_postgres_processes_cannot_withdraw_the_last_copy(): void
    {
        if (! function_exists('pcntl_fork')) {
            self::markTestSkipped('The concurrent PostgreSQL test requires the pcntl extension.');
        }

        $book = Book::register(
            'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'Última copia en carrera',
            'Autora',
            Isbn::fromString('9780306406157'),
            '11111111-1111-4111-8111-111111111111',
            Quantity::fromInt(1),
        );
        app(DatabaseBookRepository::class)->add($book);

        $directory = sys_get_temp_dir().'/library-race-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $children = [];

        try {
            for ($worker = 0; $worker < 2; $worker++) {
                $pid = pcntl_fork();
                if ($pid === -1) {
                    self::fail('Could not start the concurrent PostgreSQL worker.');
                }
                if ($pid === 0) {
                    // Never share the parent's PDO connection across forked workers.
                    DB::purge();
                    $repository = new DatabaseBookRepository(DB::connection());
                    $snapshot = $repository->findActive($book->id);
                    file_put_contents($directory.'/ready-'.$worker, (string) $snapshot?->availableCopies);
                    $deadline = microtime(true) + 20;
                    while (! is_file($directory.'/go') && microtime(true) < $deadline) {
                        usleep(1000);
                    }
                    if (! is_file($directory.'/go') || $snapshot === null) {
                        exit(2);
                    }
                    $changed = DB::transaction(fn (): bool => $repository->applyWithdrawal($snapshot->withdraw(), 1));
                    file_put_contents($directory.'/result-'.$worker, $changed ? 'won' : 'lost');
                    exit(0);
                }
                $children[] = $pid;
            }

            $deadline = microtime(true) + 20;
            while ((! is_file($directory.'/ready-0') || ! is_file($directory.'/ready-1')) && microtime(true) < $deadline) {
                usleep(1000);
            }
            self::assertFileExists($directory.'/ready-0');
            self::assertFileExists($directory.'/ready-1');
            self::assertSame('1', file_get_contents($directory.'/ready-0'));
            self::assertSame('1', file_get_contents($directory.'/ready-1'));
            file_put_contents($directory.'/go', 'start');

            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                self::assertTrue(pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0);
            }

            $results = [
                file_get_contents($directory.'/result-0'),
                file_get_contents($directory.'/result-1'),
            ];
            self::assertSame(1, count(array_filter($results, static fn (string $result): bool => $result === 'won')));
            self::assertSame(1, count(array_filter($results, static fn (string $result): bool => $result === 'lost')));
            self::assertSame(0, (int) DB::table('library.book')->where('id', $book->id)->value('available_copies'));
        } finally {
            file_put_contents($directory.'/go', 'cleanup');
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($directory.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
