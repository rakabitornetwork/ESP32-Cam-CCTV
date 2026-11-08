<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\HeartbeatRequest;
use Illuminate\Http\Response;

class DeviceHeartbeatController extends Controller
{
    use ResolvesDevice;

    public function __invoke(HeartbeatRequest $request): Response
    {
        $device = $this->device($request);
        $attributes = ['last_seen_at' => now()];

        if ($request->filled('latitude') && $request->filled('longitude')) {
            $attributes['latitude'] = $request->float('latitude');
            $attributes['longitude'] = $request->float('longitude');
        }

        if ($request->exists('pir_armed')) {
            $attributes['pir_armed'] = $request->boolean('pir_armed');
        }

        if ($request->filled('rssi')) {
            $attributes['rssi'] = $request->integer('rssi');
        }

        if ($request->filled('firmware')) {
            $attributes['firmware'] = $request->string('firmware')->toString();
        }

        $device->forceFill($attributes)->save();

        return response()->noContent();
    }
}
