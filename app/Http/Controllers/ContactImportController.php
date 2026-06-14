<?php

namespace App\Http\Controllers;

use App\Jobs\ImportContactsJob;
use App\Models\CustomField;
use App\Models\Pipeline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ContactImportController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $pipelines = Pipeline::where('tenant_id', $tenantId)->with('stages')->get();
        
        $customFields = CustomField::where('tenant_id', $tenantId)
            ->where('is_deleted', false)
            ->get();

        $imports = \App\Models\BatchImport::where('tenant_id', $tenantId)
            ->where('user_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();

        // System fields that can be mapped
        $contactFields = [
            ['key' => 'name', 'label' => 'Full Name', 'type' => 'text'],
            ['key' => 'first_name', 'label' => 'First Name', 'type' => 'text'],
            ['key' => 'last_name', 'label' => 'Last Name', 'type' => 'text'],
            ['key' => 'email', 'label' => 'Email', 'type' => 'email'],
            ['key' => 'phone', 'label' => 'Phone', 'type' => 'tel'],
            ['key' => 'company_name', 'label' => 'Company Name', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Job Title', 'type' => 'text'],
            ['key' => 'address', 'label' => 'Address', 'type' => 'text'],
            ['key' => 'city', 'label' => 'City', 'type' => 'text'],
            ['key' => 'state', 'label' => 'State', 'type' => 'text'],
            ['key' => 'zip', 'label' => 'Zip Code', 'type' => 'text'],
            ['key' => 'country', 'label' => 'Country', 'type' => 'text'],
            ['key' => 'website', 'label' => 'Website', 'type' => 'url'],
            ['key' => 'tags', 'label' => 'Tags (comma separated)', 'type' => 'text'],
        ];

        $opportunityFields = [
            ['key' => 'opp_name', 'label' => 'Opportunity Name', 'type' => 'text'],
            ['key' => 'opp_value', 'label' => 'Opportunity Value', 'type' => 'number'],
            ['key' => 'opp_status', 'label' => 'Status (Open/Won/Lost)', 'type' => 'text'],
        ];

        return Inertia::render('Contacts/BulkImport', [
            'pipelines' => $pipelines,
            'existingCustomFields' => $customFields,
            'contactFields' => $contactFields,
            'opportunityFields' => $opportunityFields,
            'recentImports' => $imports
        ]);
    }

    public function parse(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $path = $request->file('file')->store('imports');
        $fullPath = \Illuminate\Support\Facades\Storage::path($path);

        $headers = [];
        $sampleRows = [];

        if (($handle = fopen($fullPath, 'r')) !== false) {
            // Get Headers
            $headers = fgetcsv($handle, 1000, ',');
            // Remove BOM if present
            if ($headers && isset($headers[0])) {
                $headers[0] = preg_replace('/\x{FEFF}/u', '', $headers[0]);
            }

            // Get first 3 rows for preview
            for ($i = 0; $i < 3; $i++) {
                $row = fgetcsv($handle, 1000, ',');
                if ($row) {
                    $sampleRows[] = $row;
                }
            }
            fclose($handle);
        }

        return response()->json([
            'filePath' => $path,
            'headers' => $headers,
            'sampleRows' => $sampleRows,
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'filePath' => 'required|string',
            'mapping' => 'required|array',
            'pipelineId' => 'nullable|exists:pipelines,id',
            'stageId' => 'nullable|exists:pipeline_stages,id',
        ]);

        $filePath = $request->input('filePath');
        $mapping = $request->input('mapping');
        $pipelineId = $request->input('pipelineId');
        $stageId = $request->input('stageId');
        $newCustomFields = $request->input('newCustomFields', []); 

        // Create new custom fields first if requested
        $createdFieldMap = []; 
        $tenantId = auth()->user()->tenant_id;

        foreach ($newCustomFields as $newField) {
            $contactObjectId = \App\Models\CustomFieldObject::where('name', 'contact')->orWhere('name', 'contacts')->value('id') ?? 1;

            $field = CustomField::create([
                'tenant_id' => $tenantId,
                'object_id' => $contactObjectId,
                'folder_id' => null, 
                'name' => $newField['name'],
                'key' => \Illuminate\Support\Str::slug($newField['name']) . '_' . uniqid(),
                'field_type' => $newField['type'] ?? 'text',
                'ui_type' => 'input', 
                'is_required' => false,
                'is_unique' => false,
                'is_system' => false,
            ]);
            
             if (isset($newField['csvHeader'])) {
                 $createdFieldMap[$newField['csvHeader']] = 'cf_' . $field->id;
             }
        }

        foreach ($mapping as $csvHeader => $target) {
            if ($target === 'CREATE_NEW' && isset($createdFieldMap[$csvHeader])) {
                $mapping[$csvHeader] = $createdFieldMap[$csvHeader];
            }
        }

        // Create Batch Import Record
        $batch = \App\Models\BatchImport::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'file_name' => basename($filePath),
            'status' => 'pending',
            'total_rows' => 0, // Will be updated in job
        ]);

        // Dispatch Job
        ImportContactsJob::dispatch(
            auth()->user(),
            $filePath,
            $mapping,
            $pipelineId,
            $stageId,
            $tenantId,
            $batch->id
        );

        return redirect()->route('contacts.bulk-import.index')->with('message', 'Import queued successfully.');
    }
}
