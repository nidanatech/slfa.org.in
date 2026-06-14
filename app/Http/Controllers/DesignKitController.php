<?php

namespace App\Http\Controllers;

use App\Models\DesignKit;
use App\Http\Requests\StoreDesignKitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DesignKitController extends Controller
{
    public function index()
    {
        $designKits = DesignKit::forTenant(auth()->user()->tenant_id)
            ->active()
            ->latest()
            ->get()
            ->map(function ($kit) {
                return [
                    'id' => $kit->id,
                    'name' => $kit->name,
                    'template_type' => $kit->template_type,
                    'logo_vertical_url' => $kit->logo_vertical_url,
                    'logo_horizontal_url' => $kit->logo_horizontal_url,
                    'colors' => $kit->colors ?? [],
                    'fonts' => $kit->fonts ?? [],
                    'created_at' => $kit->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('Marketing/DesignKit/Index', [
            'designKits' => $designKits
        ]);
    }

    public function create(Request $request)
    {
        $template = $request->query('template');
        
        // If no template specified, show template selection
        if (!$template) {
            return Inertia::render('Marketing/DesignKit/SelectTemplate');
        }

        // Template presets with colors and fonts
        $templatePresets = $this->getTemplatePresets($template);

        return Inertia::render('Marketing/DesignKit/Editor', [
            'designKit' => null,
            'selectedTemplate' => $template,
            'templatePresets' => $templatePresets
        ]);
    }

    private function getTemplatePresets($template)
    {
        $presets = [
            'modern' => [
                'colors' => ['#6366F1', '#8B5CF6', '#EC4899', '#F59E0B', '#10B981'],
                'fonts' => ['Inter', 'Poppins', 'Space Grotesk'],
                'description' => 'Clean, contemporary design with vibrant gradients'
            ],
            'classic' => [
                'colors' => ['#1E3A8A', '#991B1B', '#065F46', '#92400E', '#1F2937'],
                'fonts' => ['Playfair Display', 'Merriweather', 'Lora'],
                'description' => 'Timeless elegance with traditional serif fonts'
            ],
            'minimal' => [
                'colors' => ['#000000', '#374151', '#6B7280', '#D1D5DB', '#FFFFFF'],
                'fonts' => ['Helvetica', 'Arial', 'Roboto'],
                'description' => 'Simple, clean design with neutral tones'
            ],
            'bold' => [
                'colors' => ['#DC2626', '#EA580C', '#D97706', '#CA8A04', '#65A30D'],
                'fonts' => ['Montserrat', 'Bebas Neue', 'Oswald'],
                'description' => 'High-impact design with strong colors'
            ],
            'elegant' => [
                'colors' => ['#9333EA', '#DB2777', '#BE185D', '#831843', '#4C1D95'],
                'fonts' => ['Cormorant Garamond', 'Crimson Text', 'EB Garamond'],
                'description' => 'Sophisticated design with refined typography'
            ],
            'playful' => [
                'colors' => ['#F472B6', '#FB923C', '#FBBF24', '#34D399', '#60A5FA'],
                'fonts' => ['Quicksand', 'Nunito', 'Comfortaa'],
                'description' => 'Fun, energetic design with bright colors'
            ],
            'professional' => [
                'colors' => ['#1E40AF', '#0F766E', '#15803D', '#B45309', '#4C1D95'],
                'fonts' => ['Open Sans', 'Lato', 'Source Sans Pro'],
                'description' => 'Corporate-ready design with trustworthy colors'
            ],
            'creative' => [
                'colors' => ['#7C3AED', '#EC4899', '#F59E0B', '#10B981', '#3B82F6'],
                'fonts' => ['Raleway', 'Josefin Sans', 'Archivo'],
                'description' => 'Artistic design with unique color combinations'
            ],
        ];

        return $presets[$template] ?? $presets['modern'];
    }

    public function store(StoreDesignKitRequest $request)
    {
        $data = $request->validated();

        // Handle logo uploads
        if ($request->hasFile('logo_vertical')) {
            $data['logo_vertical'] = $request->file('logo_vertical')
                ->store('design-kits/logos', 'public');
        }

        if ($request->hasFile('logo_horizontal')) {
            $data['logo_horizontal'] = $request->file('logo_horizontal')
                ->store('design-kits/logos', 'public');
        }

        $designKit = DesignKit::create([
            ...$data,
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('marketing.design-kit.index')
            ->with('success', 'Design kit created successfully!');
    }

    public function edit($id)
    {
        $designKit = DesignKit::forTenant(auth()->user()->tenant_id)
            ->findOrFail($id);

        return Inertia::render('Marketing/DesignKit/Editor', [
            'designKit' => [
                'id' => $designKit->id,
                'name' => $designKit->name,
                'template_type' => $designKit->template_type,
                'logo_vertical_url' => $designKit->logo_vertical_url,
                'logo_horizontal_url' => $designKit->logo_horizontal_url,
                'colors' => $designKit->colors ?? [],
                'fonts' => $designKit->fonts ?? [],
            ],
            'selectedTemplate' => null
        ]);
    }

    public function update(StoreDesignKitRequest $request, $id)
    {
        $designKit = DesignKit::forTenant(auth()->user()->tenant_id)
            ->findOrFail($id);

        $data = $request->validated();

        // Handle logo uploads
        if ($request->hasFile('logo_vertical')) {
            // Delete old logo
            if ($designKit->logo_vertical) {
                Storage::disk('public')->delete($designKit->logo_vertical);
            }
            $data['logo_vertical'] = $request->file('logo_vertical')
                ->store('design-kits/logos', 'public');
        }

        if ($request->hasFile('logo_horizontal')) {
            // Delete old logo
            if ($designKit->logo_horizontal) {
                Storage::disk('public')->delete($designKit->logo_horizontal);
            }
            $data['logo_horizontal'] = $request->file('logo_horizontal')
                ->store('design-kits/logos', 'public');
        }

        $designKit->update($data);

        return redirect()
            ->route('marketing.design-kit.index')
            ->with('success', 'Design kit updated successfully!');
    }

    public function destroy($id)
    {
        $designKit = DesignKit::forTenant(auth()->user()->tenant_id)
            ->findOrFail($id);

        // Delete associated logos
        $designKit->deleteLogos();

        $designKit->delete();

        return redirect()
            ->route('marketing.design-kit.index')
            ->with('success', 'Design kit deleted successfully!');
    }

    public function duplicate($id)
    {
        $original = DesignKit::forTenant(auth()->user()->tenant_id)
            ->findOrFail($id);

        $duplicate = $original->replicate();
        $duplicate->name = $original->name . ' (Copy)';
        $duplicate->user_id = auth()->id();
        $duplicate->save();

        return redirect()
            ->route('marketing.design-kit.index')
            ->with('success', 'Design kit duplicated successfully!');
    }
}
