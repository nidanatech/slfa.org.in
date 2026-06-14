<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWithinQuota
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $user = $request->user();
        if (!$user || !$user->tenant) {
            return $next($request);
        }

        if (!$user->tenant->canCreate($resource)) {
            $limit = $user->tenant->getQuotaLimit($resource);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "You have reached your limit of {$limit} " . str_replace('_', ' ', $resource) . " for this plan.",
                    'resource' => $resource,
                    'limit' => $limit
                ], 403);
            }

            return back()->with('error', "Limit reached: You can only have {$limit} " . str_replace('_', ' ', $resource) . " on your current plan.");
        }

        return $next($request);
    }
}
