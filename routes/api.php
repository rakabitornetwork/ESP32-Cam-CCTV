<?php

use App\Http\Controllers\Api\V1\CaptureStoreController;
use App\Http\Controllers\Api\V1\DeviceHeartbeatController;
use App\Http\Controllers\Api\V1\DeviceMeController;
use App\Http\Controllers\Api\V1\RecordingChunkController;
use App\Http\Controllers\Api\V1\RecordingCompleteController;
use App\Http\Controllers\Api\V1\RecordingInitController;
use App\Http\Middleware\AuthenticateDevice;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([AuthenticateDevice::class, 'throttle:60,1'])->group(function () {
    Route::get('devices/me', DeviceMeController::class);
    Route::post('devices/heartbeat', DeviceHeartbeatController::class);
    Route::post('captures', CaptureStoreController::class);
    Route::post('recordings/init', RecordingInitController::class);
    Route::post('recordings/{recording}/chunk', RecordingChunkController::class);
    Route::post('recordings/{recording}/complete', RecordingCompleteController::class);
});
