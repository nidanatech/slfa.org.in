<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
        ]);

        ContactMessage::create($validated);

        // TODO: Send email notification to committee
        // Mail::to(config('mail.committee'))->send(new ContactFormSubmission($validated));

        return back()->with('success', 'Thank you for your message! We will get back to you soon.');
    }
}
