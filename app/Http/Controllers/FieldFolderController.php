<?php

namespace App\Http\Controllers;

use App\Models\FieldFolder;
use Illuminate\Http\Request;

class FieldFolderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'object_id' => 'required|exists:custom_field_objects,id',
            'name' => 'required|string|max:255',
        ]);

        $validated['tenant_id'] = \Illuminate\Support\Facades\Auth::user()->tenant_id;
        FieldFolder::create($validated);

        return redirect()->back()->with('success', 'Folder created successfully.');
    }

    public function update(Request $request, FieldFolder $fieldFolder)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $fieldFolder->update($validated);

        return redirect()->back()->with('success', 'Folder updated successfully.');
    }

    public function destroy(FieldFolder $fieldFolder)
    {
        $fieldFolder->delete(); // Cascade deletes fields or sets null? Schema says OnDelete set null for fields.
        
        return redirect()->back()->with('success', 'Folder deleted successfully.');
    }
}
