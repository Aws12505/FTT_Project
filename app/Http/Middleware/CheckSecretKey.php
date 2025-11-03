<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSecretKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the secret key from the request header (case-insensitive)
        $secretKey = $request->header('X_SECRET_KEY');
        // Get the secret key from the .env file
        $expectedSecretKey = config('app.X_SECRET_KEY');
        
        if ($secretKey !== $expectedSecretKey) {
            // If it doesn't match, return unauthorized response
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // If the keys match, allow the request to pass through
        return $next($request);
    }
}
