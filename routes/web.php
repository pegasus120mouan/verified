<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketIntrouvableController;
use App\Http\Controllers\TicketStoreController;
use App\Http\Controllers\TicketVerifyController;
use App\Http\Controllers\UsinesCatalogController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications');
    Route::get('/verification-paie', [VerificationController::class, 'paie'])->name('verification-paie.index');
    Route::get('/verification-paie/modele-excel', [VerificationController::class, 'paieExcelTemplate'])->name('verification-paie.template');
    Route::get('/verification-paie/usine/{id_usine}', [VerificationController::class, 'paieUsine'])
        ->where('id_usine', '[0-9]+')
        ->name('verification-paie.usine');
    Route::post('/verification-paie/usine/{id_usine}/excel', [VerificationController::class, 'paieUsineVerifyExcel'])
        ->where('id_usine', '[0-9]+')
        ->name('verification-paie.excel');
    Route::post('/verification-paie/usine/{id_usine}/print-introuvables', [VerificationController::class, 'printIntrouvables'])
        ->where('id_usine', '[0-9]+')
        ->name('verification-paie.print-introuvables');
    Route::get('/verification-paie/usine/{id_usine}/print-trouves', [VerificationController::class, 'printTrouves'])
        ->where('id_usine', '[0-9]+')
        ->name('verification-paie.print-trouves');
    Route::get('/verifications/usine/{id_usine}/point-tonnage', [VerificationController::class, 'printPointTonnage'])
        ->where('id_usine', '[0-9]+')
        ->name('verifications.usine.point-tonnage');
    Route::get('/verifications/usine/{id_usine}', [VerificationController::class, 'show'])
        ->where('id_usine', '[0-9]+')
        ->name('verifications.usine');

    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');

    Route::get('/usines', [UsinesCatalogController::class, 'index'])->name('usines.index');

    Route::get('/tickets-introuvables', [TicketIntrouvableController::class, 'index'])->name('tickets-introuvables.index');
    Route::post('/tickets-introuvables/{id}/reverify', [TicketIntrouvableController::class, 'reverify'])
        ->where('id', '[0-9]+')
        ->name('tickets-introuvables.reverify');

    Route::get('/tickets/impression', [TicketController::class, 'print'])->name('tickets.print');
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

    Route::get('/api/tickets/verify', TicketVerifyController::class)->name('api.tickets.verify');
    Route::post('/api/tickets', TicketStoreController::class)->name('api.tickets.store');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
