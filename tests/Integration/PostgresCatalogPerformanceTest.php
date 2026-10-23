<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Adapters\Persistence\Database\DatabaseBookRepository;
use App\Application\Shared\Pagination;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** RNF-1: validate catalog search against 5,000 PostgreSQL rows. */
final class PostgresCatalogPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('pgsql', DB::connection()->getDriverName(), 'Integration tests require PostgreSQL.');
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_catalog_page_of_five_thousand_books_returns_under_three_hundred_milliseconds(): void
    {
        $category = '11111111-1111-4111-8111-111111111111';
        for ($offset = 0; $offset < 5000; $offset += 500) {
            $rows = [];
            for ($i = $offset; $i < min($offset + 500, 5000); $i++) {
                $rows[] = [
                    'id' => sprintf('00000000-0000-4000-8000-%012d', $i + 1),
                    'title' => sprintf('Libro %05d', $i),
                    'author' => 'Autor de rendimiento',
                    'isbn' => sprintf('978%010d', $i),
                    'category_id' => $category,
                    'total_copies' => 2,
                    'available_copies' => 2,
                    'cover_key' => null,
                    'deleted_at' => null,
                ];
            }
            DB::table('library.book')->insert($rows);
        }

        $books = app(DatabaseBookRepository::class);
        $pagination = Pagination::of(1, 20, 100, 20);
        $books->search('Libro 04', $category, $pagination); // warm the connection and query plan
        $durations = [];
        for ($run = 0; $run < 3; $run++) {
            $started = hrtime(true);
            $page = $books->search('Libro 04', $category, $pagination);
            $durations[] = (hrtime(true) - $started) / 1_000_000;
            self::assertSame(20, count($page->items));
            self::assertSame(1000, $page->total);
        }

        self::assertLessThan(300.0, min($durations), sprintf(
            'Fastest of three 5,000-book catalog searches took %.1f ms (durations: %s).',
            min($durations),
            implode(', ', array_map(static fn (float $ms): string => number_format($ms, 1), $durations)),
        ));
    }
}
