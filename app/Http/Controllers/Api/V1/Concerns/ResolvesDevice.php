<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Device;
use Illuminate\Http\Request;

trait ResolvesDevice
{
    protected function device(Request $request): Device
    {
        $device = $request->attributes->get('device');

        abort_unless($device instanceof Device, 401);

        return $device;
    }
}
