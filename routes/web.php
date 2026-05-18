<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ShareDocumentController;

// Export routes
Route::get('/documents/{document}/export/pdf', [ExportController::class, 'pdf'])
    ->middleware(['auth'])
    ->name('documents.export.pdf');
    
Route::middleware(['auth'])->group(function () {
    Route::resource('documents', DocumentController::class);
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ SHARE & VERSION HISTORY ROUTES
Route::middleware(['auth'])->prefix('api')->group(function () {
    // Share routes
    Route::get('/documents/{document}/users', [ShareDocumentController::class, 'index']);
    Route::post('/documents/{document}/users', [ShareDocumentController::class, 'store']);
    Route::patch('/documents/{document}/users/{documentUser}', [ShareDocumentController::class, 'update']);
    Route::delete('/documents/{document}/users/{documentUser}', [ShareDocumentController::class, 'destroy']);
    
    // ✅ VERSION HISTORY ROUTES (LENGKAP!)
    Route::get('/documents/{document}/versions', [DocumentController::class, 'versions']);
    Route::get('/documents/{document}/versions/{version}', [DocumentController::class, 'getVersion']); // ✅ BARU!
    Route::post('/documents/{document}/versions', [DocumentController::class, 'saveVersion']);
    Route::post('/documents/{document}/versions/{version}/restore', [DocumentController::class, 'restoreVersion']);
});

// Typing indicator
Route::middleware(['auth'])->patch('/documents/{document}/typing', [DocumentController::class, 'typing']);

require __DIR__.'/auth.php';