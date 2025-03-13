<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id'); // Assuming the ID is passed via route if needed for exclusion

        return [
            'company_id' => 'required|integer|exists:companies,id',
            'nip' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'avatar' => 'nullable|url',
            'status' => 'required|string|in:active,inactive',
            'role' => 'required|array',
            'phone' => 'nullable|string|max:20',
            'placebirth' => 'nullable|string|max:255',
            'datebirth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female',
            'blood' => 'nullable|string|in:A,B,AB,O',
            'marital_status' => 'nullable|string|in:single,married',
            'religion' => 'nullable|string|max:255',
            'identity_type' => 'nullable|string|max:255',
            'identity_numbers' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'citizen_address' => 'nullable|string|max:255',
            'residential_address' => 'nullable|string|max:255',
            'basic_salary' => 'nullable|numeric',
            'payment_type' => 'nullable|string|max:255',
            'departement_id' => 'nullable|integer|exists:departements,id',
            'job_position_id' => 'nullable|integer|exists:job_positions,id',
            'job_level_id' => 'nullable|integer|exists:job_levels,id',
            'approval_line_id' => 'nullable|integer|exists:approval_lines,id',
            'approval_manager_id' => 'nullable|integer|exists:users,id',
            'join_date' => 'nullable|date',
            'sign_date' => 'nullable|date',
            'resign_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:255',
            'bank_number' => 'nullable|string|max:255',
            'bank_holder' => 'nullable|string|max:255',

            // Family and Education validations
            'family' => 'required|array',
            'family.*.id' => 'required|integer',
            'family.*.fullname' => 'required|string|max:255',
            'family.*.relationship' => 'required|string|max:255',
            'family.*.birthdate' => 'required|date',
            'family.*.marital_status' => 'required|string|in:single,married',
            'family.*.job' => 'required|string|max:255',

            'formal_education' => 'required|array',
            'formal_education.*.id' => 'required|integer',
            'formal_education.*.institution' => 'required|string|max:255',
            'formal_education.*.majors' => 'required|string|max:255',
            'formal_education.*.score' => 'required|numeric',
            'formal_education.*.start' => [
                'required',
                'regex:/^\d{4}$/',
                'before_or_equal:formal_education.*.finish',
            ],
            'formal_education.*.finish' => [
                'required',
                'regex:/^\d{4}$/',
                'after_or_equal:formal_education.*.start',
            ],
            'formal_education.*.status' => 'required|string|in:passed, not-passed, in-progress',
            'formal_education.*.certification' => 'required|boolean',

            'informal_education' => 'required|array',
            'informal_education.*.id' => 'required|integer',
            'informal_education.*.institution' => 'required|string|max:255',
            'informal_education.*.start' => [
                'required',
                'regex:/^\d{4}$/',
                'before_or_equal:informal_education.*.finish',
            ],
            'informal_education.*.finish' => [
                'required',
                'regex:/^\d{4}$/',
                'after_or_equal:informal_education.*.start',
            ],
            'informal_education.*.type' => 'required|string|in:day, year, month',
            'informal_education.*.duration' => 'required|numeric',
            'informal_education.*.status' => 'required|string|in:passed, not-passed, in-progress',
            'informal_education.*.certification' => 'required|boolean',

            'work_experience' => 'required|array',
            'work_experience.*.id' => 'required|integer',
            'work_experience.*.company_name' => 'required|string|max:255',
            'work_experience.*.start' => [
                'required',
                'regex:/^\d{4}$/',
                'before_or_equal:informal_education.*.finish',
            ],
            'work_experience.*.finish' => [
                'required',
                'regex:/^\d{4}$/',
                'after_or_equal:informal_education.*.start',
            ],
            'work_experience.*.position' => 'required|string|max:255',
            'work_experience.*.certification' => 'required|boolean',
        ];
    }

    /**
     * Configure the error messages for the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'The email has already been taken.',
            'phone.max' => 'The phone number should not exceed 20 characters.',
            // Add more custom messages as needed
        ];
    }

    /**
     * Configure the attributes for the request.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'Company',
            'nip' => 'NIP',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'password' => 'Password',
            // Add other attributes for friendly field names
        ];
    }
}
