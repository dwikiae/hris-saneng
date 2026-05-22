<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IPWhitelist
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();

        if ($this->isAllowed($ip)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'errors.company_network_only',
        ], 403);
    }

    private function isAllowed(string $ip): bool
    {
        $allowedIps = array_filter(array_map('trim', explode(',', (string) env('APP_ALLOWED_IPS', '127.0.0.1,::1'))));

        foreach ($allowedIps as $allowedIp) {
            if ($ip === $allowedIp || $this->matchesCidr($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
