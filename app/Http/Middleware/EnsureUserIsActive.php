<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deactivated accounts cannot use the API, even with a token issued before
 * they were deactivated.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            return response()->json(['message' => 'This account is deactivated.'], 403);
        }

        return $next($request);
    }
}
