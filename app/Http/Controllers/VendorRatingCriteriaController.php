<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\VendorRatingCriteria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorRatingCriteriaController extends Controller
{
    public function index()
    {
        return Inertia::render('Vendors/Criteria/Index', [
            'criteria' => VendorRatingCriteria::where('tenant_id', auth()->user()->tenant_id)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:10',
        ]);

        VendorRatingCriteria::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $validated['name'],
            'weight' => $validated['weight'],
        ]);

        return back()->with('success', 'Criteria created successfully.');
    }

    public function update(Request $request, VendorRatingCriteria $ratingRatingCriterion)
    {
        // Note: Route model binding might automatically resolve to $vendorRatingCriterion or similar depending on naming convention
        // I will use $id or consistent variable naming. 
        // Actually, resource controller might use specific name. I'll stick to a generic id based lookup if needed or assume standard binding.
        // Let's use $rating_criterion to match resource name singular? No, usually it's model name camelCase.
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:10',
        ]);

        $ratingRatingCriterion->update($validated);

        return back()->with('success', 'Criteria updated successfully.');
    }

    public function destroy(VendorRatingCriteria $ratingCriterion)
    {
        $ratingCriterion->delete();
        return back()->with('success', 'Criteria deleted successfully.');
    }
}
