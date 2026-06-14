<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function index()
    {
        return Inertia::render('Campaigns/Index');
    }

    public function create()
    {
        return Inertia::render('Campaigns/Create');
    }

    public function store(Request $request)
    {
        // Placeholder
        return redirect()->route('campaigns.index');
    }
}
