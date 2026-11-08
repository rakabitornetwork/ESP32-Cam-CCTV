<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('devices/map', [DeviceController::class, 'map'])->name('devices.map');
    Route::post('devices/{device}/rotate-token', [DeviceController::class, 'rotateToken'])->name('devices.rotate-token');
    Route::resource('devices', DeviceController::class);

    Route::get('recordings', [RecordingController::class, 'index'])->name('recordings.index');
    Route::get('recordings/{recording}', [RecordingController::class, 'show'])->name('recordings.show');
    Route::get('recordings/{recording}/stream', [RecordingController::class, 'stream'])->name('recordings.stream');
    Route::get('recordings/{recording}/download', [RecordingController::class, 'download'])->name('recordings.download');
    Route::delete('recordings/{recording}', [RecordingController::class, 'destroy'])->name('recordings.destroy');

    Route::get('captures', [CaptureController::class, 'index'])->name('captures.index');
    Route::get('captures/{capture}/stream', [CaptureController::class, 'stream'])->name('captures.stream');
    Route::delete('captures/{capture}', [CaptureController::class, 'destroy'])->name('captures.destroy');

    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing', [BillingController::class, 'store'])->name('billing.store');
    Route::post('billing/invoices/{invoice}/pay', [BillingController::class, 'pay'])->name('billing.pay');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('admin', AdminDashboardController::class)->middleware('admin')->name('admin.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
