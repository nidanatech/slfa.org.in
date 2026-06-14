<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDesignKitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'template_type' => 'nullable|string|in:modern,classic,minimal,bold,elegant,playful,professional,creative',
            'logo_vertical' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120', // 5MB
            'logo_horizontal' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:5120',
            'colors' => 'nullable|array|max:10',
            'colors.*' => 'string|regex:/^#[0-9A-Fa-f]{6}$/',
            'fonts' => 'nullable|array|max:5',
            'fonts.*' => 'string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Design kit name is required.',
            'logo_vertical.image' => 'Vertical logo must be an image file.',
            'logo_vertical.max' => 'Vertical logo must not exceed 5MB.',
            'logo_horizontal.image' => 'Horizontal logo must be an image file.',
            'logo_horizontal.max' => 'Horizontal logo must not exceed 5MB.',
            'colors.*.regex' => 'Each color must be a valid hex color code (e.g., #FF5733).',
        ];
    }
}
