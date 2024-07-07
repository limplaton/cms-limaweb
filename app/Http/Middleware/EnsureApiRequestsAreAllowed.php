<?php
 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiRequestsAreAllowed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if ($request->user() &&
            $request->bearerToken() &&
            $request->user()->cant('access-api')) {
            return response()->json([
                'error' => 'Your account is not authorized to perform API requests.',
            ], 403);
        }

        return $next($request);
    }
}
