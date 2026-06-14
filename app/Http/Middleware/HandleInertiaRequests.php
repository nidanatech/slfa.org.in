<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $userAgent = $request->header('User-Agent');
        // Improved mobile detection - excludes tablets and is more specific
        $isMobile = (bool) preg_match('/(android.*mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino)/i', $userAgent) 
            && !preg_match('/(tablet|ipad|playbook|silk)/i', $userAgent);

        return [
            ...parent::share($request),
            'isMobile' => $isMobile,
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'tenants' => $request->user()->tenants()->with('businessProfile')->get(),
                    'tenant' => $request->user()->tenant ? $request->user()->tenant->load('businessProfile') : null,
                ]) : null,
                'unreadNotificationsCount' => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
                'notifications' => $request->user() ? $request->user()->notifications()->take(5)->get() : [],
                'assigned_messages_count' => $request->user() 
                    ? \App\Models\Conversation::where('assigned_to', $request->user()->id)
                        ->where('status', 'open')
                        ->count() 
                    : 0,
                'quotaStatus' => ($request->user() && $request->user()->tenant)
                    ? app(\App\Services\QuotaService::class)->getQuotaStatus($request->user()->tenant)
                    : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
                'warning' => $request->session()->get('warning'),
                'formSubmitted' => $request->session()->get('formSubmitted'),
            ],
        ];
    }
}
