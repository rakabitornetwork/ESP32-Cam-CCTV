<?php

use App\Http\Controllers\Admin\UpdateController as AdminUpdateController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'captures.index' : 'login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn () => redirect()->route('captures.index'))->name('dashboard');

    Route::get('kamera', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('kamera', [DeviceController::class, 'store'])->name('devices.store');
    Route::patch('kamera/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::delete('kamera/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

    Route::get('captures', [CaptureController::class, 'index'])->name('captures.index');
    Route::get('captures/{capture}/stream', [CaptureController::class, 'stream'])->name('captures.stream');
    Route::patch('captures/{capture}/rotation', [CaptureController::class, 'rotate'])->name('captures.rotate');
    Route::delete('captures/{capture}', [CaptureController::class, 'destroy'])->name('captures.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('pembaruan', [AdminUpdateController::class, 'show'])->name('update');
        Route::post('pembaruan/periksa', [AdminUpdateController::class, 'check'])->name('update.check');
        Route::post('pembaruan', [AdminUpdateController::class, 'apply'])->name('update.apply');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
