<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    public function index()
    {
        return Inertia::render('Workflows/Index');
    }

    public function create()
    {
        return Inertia::render('Workflows/Builder');
    }
    
    public function store(Request $request)
    {
        // Placeholder
        return redirect()->route('workflows.index');
    }
}
