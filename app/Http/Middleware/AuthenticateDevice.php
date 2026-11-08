<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            abort(401, 'Token perangkat wajib.');
        }

        $device = Device::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if ($device === null) {
            abort(401, 'Token perangkat tidak valid.');
        }

        $device->touchLastSeen();
        $request->attributes->set('device', $device);

        return $next($request);
    }
}
