<?php

use App\Http\Controllers\DayCompletionController;
use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\GateResultController;
use App\Http\Controllers\MistakeController;
use App\Http\Controllers\MonthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\StudySettingsController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\WeekController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', TodayController::class)->name('dashboard');

    Route::put('/days/{roadmapDay:number}/completion', [DayCompletionController::class, 'update'])->name('days.completion.update');
    Route::delete('/days/{roadmapDay:number}/completion', [DayCompletionController::class, 'destroy'])->name('days.completion.destroy');

    Route::get('/roadmap', RoadmapController::class)->name('roadmap');
    Route::get('/roadmap/months/{month:number}', [MonthController::class, 'show'])->name('roadmap.months.show');
    Route::get('/roadmap/weeks/{week:number}', [WeekController::class, 'show'])->name('roadmap.weeks.show');

    Route::put('/months/{month:number}/gate', [GateResultController::class, 'update'])->name('months.gate.update');
    Route::delete('/months/{month:number}/gate', [GateResultController::class, 'destroy'])->name('months.gate.destroy');

    Route::get('/settings', [StudySettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [StudySettingsController::class, 'update'])->name('settings.update');

    Route::get('/flashcards', [FlashcardController::class, 'index'])->name('flashcards');
    Route::post('/flashcards/{card}/review', [FlashcardController::class, 'review'])->name('flashcards.review');

    Route::get('/mistakes', [MistakeController::class, 'index'])->name('mistakes');
    Route::post('/mistakes', [MistakeController::class, 'store'])->name('mistakes.store');
    Route::get('/mistakes/{mistake}/edit', [MistakeController::class, 'edit'])->name('mistakes.edit');
    Route::patch('/mistakes/{mistake}', [MistakeController::class, 'update'])->name('mistakes.update');
    Route::delete('/mistakes/{mistake}', [MistakeController::class, 'destroy'])->name('mistakes.destroy');
    Route::post('/mistakes/{mistake}/repeat', [MistakeController::class, 'repeat'])->name('mistakes.repeat');
    Route::post('/mistakes/{mistake}/pin', [MistakeController::class, 'pin'])->name('mistakes.pin');
    Route::delete('/mistakes/{mistake}/pin', [MistakeController::class, 'unpin'])->name('mistakes.unpin');

    // Placeholders until each feature lands (see docs/ROADMAP.md).
    Route::view('/scores', 'coming-soon', ['title' => 'Điểm số', 'phase' => 'Phase 6'])->name('scores');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
