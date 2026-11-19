<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreMembershipApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $profile = $this->input('profile', []);
        if (is_array($profile)) {
            foreach (['address', 'mobile', 'whatsapp', 'landline', 'personal_email', 'business_name', 'occupation'] as $key) {
                if (isset($profile[$key]) && is_string($profile[$key])) {
                    $profile[$key] = trim($profile[$key]);
                }
            }
        }

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? strtolower(trim($this->input('email'))) : $this->input('email'),
            'profile' => $profile,
        ]);
    }

    public function rules(): array
    {
        $business = $this->input('membership_type') === 'business';
        $phone = ['required', 'string', 'regex:/^\+?[0-9][0-9\s().-]{6,24}$/'];

        return [
            'membership_type' => ['required', Rule::in(['individual', 'business'])],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'profile' => ['required', 'array:address,landline,mobile,whatsapp,personal_email,date_of_birth,gender,occupation,business_name,nature_of_business,date_of_incorporation,connection_with_srilanka'],
            'profile.address' => ['required', 'string', 'min:8', 'max:2000'],
            'profile.mobile' => $phone,
            'profile.landline' => ['nullable', 'string', 'regex:/^\+?[0-9][0-9\s().-]{6,24}$/'],
            'profile.whatsapp' => ['nullable', 'string', 'regex:/^\+?[0-9][0-9\s().-]{6,24}$/'],
            'profile.personal_email' => ['nullable', 'email', 'max:255'],
            'profile.date_of_birth' => [$business ? 'nullable' : 'required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'profile.gender' => ['nullable', Rule::in(['Male', 'Female', 'Others'])],
            'profile.occupation' => ['nullable', 'string', 'max:255'],
            'profile.business_name' => [$business ? 'required' : 'nullable', 'string', 'max:255'],
            'profile.nature_of_business' => ['nullable', Rule::in(['Company', 'LLP', 'Society', 'Firm', 'Others'])],
            'profile.date_of_incorporation' => [$business ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'profile.connection_with_srilanka' => ['required', 'array', 'min:1'],
            'profile.connection_with_srilanka.*' => [Rule::in(['Origin', 'Ancestral', 'Ancestry', 'Trade / Employment', 'Education', 'Spouse is Sri Lankan'])],
            'family_members' => ['nullable', 'array', 'max:10'],
            'family_members.*' => ['array:name,relationship,gender,date_of_birth'],
            'family_members.*.name' => ['required', 'string', 'max:255'],
            'family_members.*.relationship' => ['required', 'string', 'max:255'],
            'family_members.*.gender' => ['nullable', Rule::in(['Male', 'Female', 'Others'])],
            'family_members.*.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'business_reps' => [$business ? 'required' : 'nullable', 'array', $business ? 'size:2' : 'max:0'],
            'business_reps.*' => ['array:name,date_of_birth,gender,designation,email,contact_number'],
            'business_reps.*.name' => ['required', 'string', 'max:255'],
            'business_reps.*.designation' => ['required', 'string', 'max:255'],
            'business_reps.*.email' => ['required', 'email', 'max:255'],
            'business_reps.*.contact_number' => $phone,
            'business_reps.*.date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'business_reps.*.gender' => ['nullable', Rule::in(['Male', 'Female', 'Others'])],
            'id_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'directory_consent' => ['required', 'boolean'],
            'accept_declaration' => ['accepted'],
        ];
    }
}
