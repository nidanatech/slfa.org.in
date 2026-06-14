<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        
        $contacts = Contact::where('tenant_id', $tenant->id)
            ->with('tags:id,name')
            ->select('id', 'email', 'name', 'first_name', 'last_name')
            ->orderBy('name')
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'email' => $contact->email,
                    'name' => $contact->name ?? ($contact->first_name . ' ' . $contact->last_name),
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'tags' => $contact->tags,
                ];
            });

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }
}
