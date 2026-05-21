<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordReset
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->force_password_reset) {
            return response()->json([
                'success' => false,
                'message' => 'auth.password.force_reset_required',
            ], 403);
        }

        return $next($request);
    }
}
