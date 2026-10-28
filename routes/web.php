<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Placeholders until each feature lands (see docs/ROADMAP.md).
    Route::view('/roadmap', 'coming-soon', ['title' => 'Lộ trình', 'phase' => 'Phase 3'])->name('roadmap');
    Route::view('/flashcards', 'coming-soon', ['title' => 'Flashcard', 'phase' => 'Phase 4'])->name('flashcards');
    Route::view('/mistakes', 'coming-soon', ['title' => 'Sổ lỗi', 'phase' => 'Phase 5'])->name('mistakes');
    Route::view('/scores', 'coming-soon', ['title' => 'Điểm số', 'phase' => 'Phase 6'])->name('scores');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
