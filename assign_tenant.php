<?php
$user = App\Models\User::first();
if ($user) {
    // Ensure Tenant model exists, assuming simple name field based on controller usage
    // Use firstOrCreate if possible, or create new
    // Check if Tenant class exists
    if (class_exists('App\Models\Tenant')) {
        $tenant = App\Models\Tenant::first();
        if (!$tenant) {
             $tenant = new App\Models\Tenant();
             $tenant->name = 'Default Tenant';
             // add other required fields if any, assuming minimal for now
             $tenant->save();
        }
        
        $user->tenant_id = $tenant->id;
        $user->save();
        echo "Assigned Tenant ID: " . $tenant->id . " to User ID: " . $user->id;
    } else {
        echo "Tenant model not found.";
    }
} else {
    echo "No user found.";
}
exit;
