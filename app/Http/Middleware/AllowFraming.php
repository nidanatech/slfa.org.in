<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowFraming
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove X-Frame-Options to allow framing
        $response->headers->remove('X-Frame-Options');

        // Allow any domain to embed this via CSP
        // We use true as the third parameter to ensure it overwrites any default CSP
        $response->headers->set('Content-Security-Policy', "frame-ancestors * https:", true);

        return $response;
    }
}
