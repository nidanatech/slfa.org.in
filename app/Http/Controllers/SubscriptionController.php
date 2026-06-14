<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)->get();
        
        return Inertia::render('Auth/SelectPlan', [
            'plans' => $plans
        ]);
    }
}
