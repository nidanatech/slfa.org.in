<?php

namespace App\Http\Controllers;

use App\Models\VendorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class VendorDocumentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,png,doc,docx|max:10240', // 10MB
            'type' => 'required|string|in:contract,license,nda,other',
        ]);

        $vendor = Auth::user()->vendor;
        
        $path = $request->file('file')->store('vendor_documents', 'public');
        $originalName = $request->file('file')->getClientOriginalName();

        $vendor->documents()->create([
            'name' => $originalName,
            'file_path' => $path,
            'type' => $request->type,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function destroy(VendorDocument $document)
    {
        // ... (existing code)
        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function verify(VendorDocument $document, Request $request)
    {
        // Admin only check (middleware handled in route?)
        // Assuming this route is protected by auth and maybe admin check
        
        $document->update([
            'status' => $request->status, // 'verified' or 'rejected'
            'verification_note' => $request->note,
        ]);

        return back()->with('success', 'Document status updated.');
    }
}
