<?php

declare(strict_types=1);

use App\Adapters\Http\Controller\AuthController;
use App\Adapters\Http\Controller\BookController;
use App\Adapters\Http\Controller\BookCoverController;
use App\Adapters\Http\Controller\CategoryController;
use App\Adapters\Http\Controller\HealthController;
use App\Adapters\Http\Controller\LoanController;
use App\Adapters\Http\Controller\MemberController;
use App\Adapters\Http\Controller\ReportController;
use App\Adapters\Http\Controller\UserController;
use Illuminate\Support\Facades\Route;

/*
 * Las rutas de api-contract.md §3, bajo /api/v1.
 *
 * Público: solo el inicio de sesión y la señal de vida. Todo lo demás pasa por
 * el token y, cuando el contrato lo pide, por un rol (CA-01.3).
 */

Route::post('auth/login', [AuthController::class, 'store']);
Route::get('health', HealthController::class);

Route::middleware('token')->group(function (): void {
    Route::get('categories', [CategoryController::class, 'index']);

    // Catálogo: leer lo puede cualquier usuario autenticado; mantenerlo, solo admin.
    Route::get('books', [BookController::class, 'index']);
    Route::get('books/{book}', [BookController::class, 'show']);
    Route::get('books/{book}/cover', [BookCoverController::class, 'show']);

    Route::middleware('role:admin')->group(function (): void {
        Route::post('users', [UserController::class, 'store']);

        Route::post('books', [BookController::class, 'store']);
        Route::put('books/{book}', [BookController::class, 'update']);
        Route::delete('books/{book}', [BookController::class, 'destroy']);
        Route::put('books/{book}/cover', [BookCoverController::class, 'update']);
        Route::delete('books/{book}/cover', [BookCoverController::class, 'destroy']);

        Route::get('reports/most-borrowed', [ReportController::class, 'mostBorrowed']);
    });

    Route::middleware('role:admin,librarian')->group(function (): void {
        Route::get('members', [MemberController::class, 'index']);
        Route::get('members/{member}', [MemberController::class, 'show']);
        Route::post('members', [MemberController::class, 'store']);

        // La ruta fija va antes que la de parámetro: si no, "overdue" entraría
        // como identificador de préstamo.
        Route::get('loans/overdue', [LoanController::class, 'overdue']);
        Route::post('loans', [LoanController::class, 'store']);
        Route::get('loans', [LoanController::class, 'index']);
        Route::get('loans/{loan}', [LoanController::class, 'show']);
        Route::post('loans/{loan}/items/{item}/return', [LoanController::class, 'returnLoanItem']);
    });
});
