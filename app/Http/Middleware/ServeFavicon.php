<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

final readonly class ServeFavicon
{
    /**
     * Serve favicon.ico before the request hits the router.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->path() !== 'favicon.ico') {
            return $next($request);
        }

        $path = public_path('favicon.ico');

        if (File::exists($path)) {
            return response()->file($path, ['Content-Type' => 'image/x-icon']);
        }

        return redirect('/favicon.svg', 302);
    }
}
