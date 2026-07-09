<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $request->user()->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required permissions.',
            ], 403);
        }

        return $next($request);
    }
}
