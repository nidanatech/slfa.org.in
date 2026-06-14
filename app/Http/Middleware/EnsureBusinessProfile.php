<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessProfile;

class EnsureBusinessProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            // Check if profile exists
            $profileExists = BusinessProfile::where('tenant_id', $user->tenant_id)->exists();

            if (!$profileExists) {
                // If not trying to access onboarding or logout, redirect
                if (!$request->routeIs('onboarding.*') && 
                    !$request->routeIs('logout') &&
                    !$request->routeIs('profile.*') // Optional: allow personal profile edit?
                    ) {
                    return redirect()->route('onboarding.index');
                }
            } else {
                // Profile exists. If user tries to go to onboarding, redirect to dashboard?
                // Or allow them to edit? The user said "only after completing...". 
                // Usually once done, you don't go back to onboarding, you go to Settings.
                if ($request->routeIs('onboarding.*')) {
                    return redirect()->route('dashboard');
                }
            }
        }

        return $next($request);
    }
}
