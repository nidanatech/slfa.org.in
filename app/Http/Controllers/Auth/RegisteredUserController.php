<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Calendar;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\DashboardAssistantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, \App\Services\DashboardAssistantService $service): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $request->name . "'s Business",
            ]);

            // 2. Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'account_status' => 'active',
            ]);

            $user->tenants()->attach($tenant);

            // 4. Create Default Calendar
            Calendar::create([
                'name' => $request->name . "'s Calendar",
                'calendar_type' => 'user',
                'owner_id' => $user->id,
                'tenant_id' => $tenant->id,
                'timezone' => 'UTC',
                'color' => '#3b82f6',
                'is_default' => true,
                'visibility' => 'private',
                'slug' => strtolower(str_replace(' ', '-', $request->name)) . '-calendar-' . uniqid(),
                'slot_duration' => 30,
                'buffer_time' => 0,
                'is_public' => false,
            ]);

            return $user;
        });

        // 5. Initialize Dashboard Assistant (Best effort, don't crash registration)
        try {
            if ($user->tenant) {
                $service->ensureAssistant($user->tenant);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Could not initialize dashboard assistant for {$user->email}: " . $e->getMessage());
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('subscription.select');
    }
}
