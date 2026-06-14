<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        try {
            $image = $request->file('image');
            $tenant = auth()->user()->tenant;
            
            // Generate unique filename
            $filename = Str::random(40) . '.' . $image->getClientOriginalExtension();
            
            // Store in tenant-specific directory
            $path = $image->storeAs(
                "tenants/{$tenant->id}/email-images",
                $filename,
                'public'
            );

            // Generate URL
            $url = Storage::url($path);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }
}
