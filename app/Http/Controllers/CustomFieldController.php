<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\CustomFieldObject;
use App\Models\FieldFolder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomFieldController extends Controller
{
    /**
     * Display the custom fields settings page.
     */
    public function index()
    {
        $tenantId = \Illuminate\Support\Facades\Auth::user()->tenant_id;

        // Fetch all objects (for usage in modals/filters)
        $objects = CustomFieldObject::all();

        // Fetch all fields with relations for the "All Fields" tab
        $customFields = CustomField::where('tenant_id', $tenantId)
            ->with(['object', 'folder'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch all folders with relations
        $fieldFolders = FieldFolder::where('tenant_id', $tenantId)
            ->with(['object', 'fields'])
            ->get();

        return Inertia::render('Settings/CustomFields/Index', [
            'objects' => $objects,
            'customFields' => $customFields,
            'fieldFolders' => $fieldFolders,
        ]);
    }

    /**
     * Store a newly created custom field.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'object_id' => 'required|exists:custom_field_objects,id',
            'folder_id' => 'nullable|exists:field_folders,id',
            'name' => 'required|string|max:255',
            'field_type' => 'required|in:text,number,email,select,date,boolean,file',
            'ui_type' => 'nullable|string',
            'options_json' => 'nullable|array',
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
        ]);

        // Generate a key based on name if not provided (or just always auto-generate)
        // Key format: object_name.field_name_slug
        $object = CustomFieldObject::find($validated['object_id']);
        $slug = \Illuminate\Support\Str::slug($validated['name'], '_');
        $key = strtolower($object->name . '.' . $slug);

        // Ensure key uniqueness
        $count = 1;
        while (CustomField::where('object_id', $object->id)->where('key', $key)->exists()) {
            $key = strtolower($object->name . '.' . $slug . '_' . $count++);
        }

        CustomField::create([
            'tenant_id' => \Illuminate\Support\Facades\Auth::user()->tenant_id,
            'object_id' => $validated['object_id'],
            'folder_id' => $validated['folder_id'] ?? null,
            'name' => $validated['name'],
            'key' => $key,
            'field_type' => $validated['field_type'],
            'ui_type' => $validated['ui_type'] ?? null,
            'options_json' => $validated['options_json'],
            'is_required' => $validated['is_required'] ?? false,
            'is_unique' => $validated['is_unique'] ?? false,
        ]);

        return redirect()->back()->with('success', 'Field created successfully.');
    }

    /**
     * Update the specified custom field.
     */
    public function update(Request $request, CustomField $customField)
    {
        $validated = $request->validate([
            'folder_id' => 'nullable|exists:field_folders,id',
            'name' => 'required|string|max:255',
            'options_json' => 'nullable|array',
            'is_required' => 'boolean',
            'is_unique' => 'boolean',
        ]);

        $customField->update($validated); // Don't allow changing type or key easily to prevent data loss

        return redirect()->back()->with('success', 'Field updated successfully.');
    }

    /**
     * Remove the specified custom field.
     */
    public function destroy(CustomField $customField)
    {
        $customField->update(['is_deleted' => true]); // Soft delete behavior as per schema
        // Or actually delete if preferred, but schema said is_deleted. 
        // Let's stick to update is_deleted for now, or just delete if user wants permanent.
        // User request: "is_deleted" column implies soft delete.
        
        return redirect()->back()->with('success', 'Field deleted successfully.');
    }
}
