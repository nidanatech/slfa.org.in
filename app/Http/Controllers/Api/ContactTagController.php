<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactTag;
use Illuminate\Http\Request;

class ContactTagController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        
        $tags = ContactTag::where('tenant_id', $tenant->id)
            ->withCount('contacts')
            ->orderBy('name')
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                    'contact_count' => $tag->contacts_count,
                ];
            });

        return response()->json([
            'success' => true,
            'tags' => $tags,
        ]);
    }

    public function contacts(Request $request, $tagId)
    {
        $tenant = auth()->user()->tenant;
        
        $tag = ContactTag::where('tenant_id', $tenant->id)
            ->where('id', $tagId)
            ->with('contacts:id,email,name,first_name,last_name')
            ->firstOrFail();

        $contacts = $tag->contacts->map(function ($contact) {
            return [
                'id' => $contact->id,
                'email' => $contact->email,
                'name' => $contact->name ?? ($contact->first_name . ' ' . $contact->last_name),
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
            ];
        });

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
        ]);
    }
}
