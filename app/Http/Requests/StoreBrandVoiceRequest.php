<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandVoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_address' => 'nullable|string|max:1000',
            'phone_number' => 'nullable|string|max:50',
            'business_hours' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:2000',
            'customer_pain_points' => 'nullable|string|max:2000',
            'brand_promise' => 'nullable|string|max:2000',
            'brand_values' => 'nullable|string|max:2000',
            'brand_description' => 'nullable|string|max:2000',
            'competitive_advantage' => 'nullable|string|max:2000',
            'unique_selling_proposition' => 'nullable|string|max:2000',
            'risks_of_inaction' => 'nullable|string|max:2000',
            'call_to_action' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'brand_name.required' => 'Brand name is required.',
            'business_type.required' => 'Business type is required.',
            'company_website.url' => 'Please enter a valid website URL.',
            'company_email.email' => 'Please enter a valid email address.',
        ];
    }
}
