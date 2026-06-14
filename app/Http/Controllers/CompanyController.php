<?php

namespace App\Models;
// Fix namespace in previous step if wrong, but assuming it was correct.
// Actually this is the controller file.

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        return Inertia::render('Companies/Index', [
            'companies' => Company::withCount('contacts')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['tenant_id'] = \Illuminate\Support\Facades\Auth::user()->tenant_id;
        
        Company::create($validated);

        return redirect()->back();
    }
}
