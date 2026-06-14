<?php

namespace App\Http\Controllers;

use App\Models\BrandVoice;
use App\Http\Requests\StoreBrandVoiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandVoiceController extends Controller
{
    public function index()
    {
        $brandVoices = BrandVoice::forTenant(auth()->user()->tenant_id)
            ->active()
            ->latest()
            ->get()
            ->map(function ($voice) {
                return [
                    'id' => $voice->id,
                    'brand_name' => $voice->brand_name,
                    'business_type' => $voice->business_type,
                    'target_audience' => $voice->target_audience,
                    'brand_promise' => $voice->brand_promise,
                    'created_at' => $voice->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('Marketing/BrandVoice/Index', [
            'brandVoices' => $brandVoices
        ]);
    }

    public function create()
    {
        return Inertia::render('Marketing/BrandVoice/Form', [
            'brandVoice' => null
        ]);
    }

    public function store(StoreBrandVoiceRequest $request)
    {
        $brandVoice = BrandVoice::create([
            ...$request->validated(),
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('marketing.brand-voice.index')
            ->with('success', 'Brand voice created successfully!');
    }

    public function edit($id)
    {
        $brandVoice = BrandVoice::forTenant(auth()->user()->tenant_id)
            ->findOrFail($id);

        return Inertia::render('Marketing/BrandVoice/Form', [
            'brandVoice' => $brandVoice
        ]);
    }

    public function update(StoreBrandVoiceRequest $request, $id)
    {
        $brandVoice = BrandVoice::forTenant(auth()->user()->tenant_id)
            ->findOrFail($id);

        $brandVoice->update($request->validated());

        return redirect()
            ->route('marketing.brand-voice.index')
            ->with('success', 'Brand voice updated successfully!');
    }

    public function destroy($id)
    {
        $brandVoice = BrandVoice::forTenant(auth()->user()->tenant_id)
            ->findOrFail($id);

        $brandVoice->delete();

        return redirect()
            ->route('marketing.brand-voice.index')
            ->with('success', 'Brand voice deleted successfully!');
    }
}
