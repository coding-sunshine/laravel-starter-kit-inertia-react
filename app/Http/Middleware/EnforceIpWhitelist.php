<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to routes by IP whitelist (config: app.ip_whitelist).
 * When the whitelist is empty, all IPs are allowed. Supports CIDR notation.
 */
final class EnforceIpWhitelist
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = config('app.ip_whitelist', []);

        if (! is_array($whitelist) || $whitelist === []) {
            return $next($request);
        }

        $clientIp = $request->ip();
        abort_if($clientIp === null, 403, 'Access denied.');

        foreach ($whitelist as $entry) {
            if ($this->ipMatches($clientIp, mb_trim((string) $entry))) {
                return $next($request);
            }
        }

        abort(403, 'Access denied: your IP is not authorized.');
    }

    private function ipMatches(string $ip, string $whitelistEntry): bool
    {
        if (str_contains($whitelistEntry, '/')) {
            [$subnet, $mask] = explode('/', $whitelistEntry, 2);

            return $this->ipInCidr($ip, mb_trim($subnet), (int) mb_trim($mask));
        }

        return $ip === $whitelistEntry;
    }

    private function ipInCidr(string $ip, string $subnet, int $mask): bool
    {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
