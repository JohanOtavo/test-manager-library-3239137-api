<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Http\Controller\BookController;
use App\Adapters\Http\Controller\LoanController;
use App\Adapters\Http\Controller\MemberController;
use App\Adapters\Persistence\Database\DatabaseBookRepository;
use App\Adapters\Persistence\Database\DatabaseCategoryRepository;
use App\Adapters\Persistence\Database\DatabaseLoanRepository;
use App\Adapters\Persistence\Database\DatabaseMemberRepository;
use App\Adapters\Persistence\Database\DatabaseTransactionManager;
use App\Adapters\Persistence\Database\DatabaseUserRepository;
use App\Adapters\Persistence\Database\SqlMostBorrowedQuery;
use App\Adapters\Security\JwtTokenIssuer;
use App\Adapters\Security\NativePasswordHasher;
use App\Adapters\Storage\DiskCoverStorage;
use App\Adapters\System\DemoOffsetClock;
use App\Adapters\System\RandomUuidGenerator;
use App\Adapters\System\SystemClock;
use App\Application\Port\BookRepository;
use App\Application\Port\CategoryRepository;
use App\Application\Port\Clock;
use App\Application\Port\CoverStorage;
use App\Application\Port\IdGenerator;
use App\Application\Port\LoanRepository;
use App\Application\Port\MemberRepository;
use App\Application\Port\MostBorrowedQuery;
use App\Application\Port\PasswordHasher;
use App\Application\Port\TokenIssuer;
use App\Application\Port\TransactionManager;
use App\Application\Port\UserRepository;
use App\Application\UseCase\Book\SetBookCoverService;
use App\Domain\Loan\MemberBorrowingLimit;
use App\Domain\Shared\LoanPolicy;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

/**
 * El único punto de composición (artículo III).
 *
 * Aquí, y solo aquí, un puerto se encuentra con su adaptador. Fuera de este
 * archivo no se instancia infraestructura: por eso cambiar el disco de portadas
 * por S3 es una línea de aquí y nada más.
 */
final class PortBindingsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConnectionInterface::class, fn (): ConnectionInterface => DB::connection());

        $this->bindInfrastructure();
        $this->bindRepositories();
        $this->bindPolicies();
        $this->bindServicesWithSettings();
        $this->bindControllersWithSettings();
    }

    private function bindInfrastructure(): void
    {
        // El reloj se resuelve por petición porque el sembrador de
        // demostración puede correrlo unos días (ver DemoOffsetClock).
        $this->app->bind(Clock::class, function (): Clock {
            $clock = new SystemClock();

            if (config('library.demo_clock') !== true) {
                return $clock;
            }

            $offsetDays = (int) request()->header('X-Library-Clock-Offset-Days', '0');

            return $offsetDays === 0 ? $clock : new DemoOffsetClock($clock, $offsetDays);
        });
        $this->app->singleton(IdGenerator::class, RandomUuidGenerator::class);
        $this->app->singleton(PasswordHasher::class, NativePasswordHasher::class);

        $this->app->singleton(TokenIssuer::class, function (Container $app): TokenIssuer {
            $secret = (string) config('library.jwt_secret');

            // Sin clave de firma el sistema no arranca: un secreto ausente se
            // nota al levantar, no en la primera petición (artículo IX).
            if ($secret === '') {
                throw new RuntimeException('Falta JWT_SECRET: el sistema no puede emitir tokens.');
            }

            return new JwtTokenIssuer(
                $app->make(Clock::class),
                $secret,
                (int) config('library.token_ttl_seconds'),
            );
        });

        $this->app->singleton(CoverStorage::class, fn (Container $app): CoverStorage => new DiskCoverStorage(
            Storage::disk('covers'),
            $app->make(IdGenerator::class),
        ));

        $this->app->singleton(
            TransactionManager::class,
            fn (Container $app): TransactionManager => new DatabaseTransactionManager($app->make(ConnectionInterface::class)),
        );
    }

    private function bindRepositories(): void
    {
        $this->app->bind(CategoryRepository::class, DatabaseCategoryRepository::class);
        $this->app->bind(UserRepository::class, DatabaseUserRepository::class);
        $this->app->bind(MemberRepository::class, DatabaseMemberRepository::class);
        $this->app->bind(BookRepository::class, DatabaseBookRepository::class);
        $this->app->bind(MostBorrowedQuery::class, SqlMostBorrowedQuery::class);

        $this->app->bind(LoanRepository::class, fn (Container $app): LoanRepository => new DatabaseLoanRepository(
            $app->make(ConnectionInterface::class),
            $app->make(LoanPolicy::class),
        ));
    }

    private function bindPolicies(): void
    {
        $this->app->singleton(LoanPolicy::class, fn (): LoanPolicy => new LoanPolicy(
            (int) config('library.loan_days'),
            (int) config('library.max_open_books_per_member'),
            (int) config('library.max_items_per_loan'),
        ));

        $this->app->singleton(
            MemberBorrowingLimit::class,
            fn (Container $app): MemberBorrowingLimit => new MemberBorrowingLimit($app->make(LoanPolicy::class)),
        );
    }

    private function bindServicesWithSettings(): void
    {
        $this->app->bind(SetBookCoverService::class, fn (Container $app): SetBookCoverService => new SetBookCoverService(
            $app->make(BookRepository::class),
            $app->make(CoverStorage::class),
            (int) config('library.cover_max_bytes'),
            (array) config('library.cover_mime_types'),
        ));
    }

    /** Los controladores reciben los límites de paginación del contrato. */
    private function bindControllersWithSettings(): void
    {
        $maxSize = (int) config('library.page_size_max');
        $defaultSize = (int) config('library.page_size_default');

        $this->app->bind(BookController::class, fn (Container $app): BookController => new BookController(
            $app->make(\App\Application\UseCase\Book\SearchBooksService::class),
            $app->make(\App\Application\UseCase\Book\GetBookService::class),
            $app->make(\App\Application\UseCase\Book\CreateBookService::class),
            $app->make(\App\Application\UseCase\Book\UpdateBookService::class),
            $app->make(\App\Application\UseCase\Book\SoftDeleteBookService::class),
            $app->make(CategoryRepository::class),
            $maxSize,
            $defaultSize,
        ));

        $this->app->bind(MemberController::class, fn (Container $app): MemberController => new MemberController(
            $app->make(\App\Application\UseCase\Member\SearchMembersService::class),
            $app->make(\App\Application\UseCase\Member\GetMemberService::class),
            $app->make(\App\Application\UseCase\Member\CreateMemberService::class),
            $maxSize,
            $defaultSize,
        ));

        $this->app->bind(LoanController::class, fn (Container $app): LoanController => new LoanController(
            $app->make(\App\Application\UseCase\Loan\LendBooksService::class),
            $app->make(\App\Application\UseCase\Loan\ReturnLoanItemService::class),
            $app->make(\App\Application\UseCase\Loan\GetLoanService::class),
            $app->make(\App\Application\UseCase\Loan\SearchLoansService::class),
            $app->make(\App\Application\UseCase\Loan\ListOverdueService::class),
            $app->make(Clock::class),
            $maxSize,
            $defaultSize,
        ));
    }
}
