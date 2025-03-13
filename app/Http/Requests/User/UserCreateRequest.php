<?php

namespace App\Http\Requests\User;

class UserCreateRequest
{
    public static function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'nip' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'avatar' => 'required|mimes:jpeg,jpg,png|max:10000',
            'status' => 'required|string|in:active,inactive',
            'role' => 'required|array',
            'phone' => 'required|string|max:20',
            'placebirth' => 'required|string|max:255',
            'datebirth' => 'required|date:format:Y-m-d',
            'gender' => 'required|string|in:m,w',
            'blood' => 'required|string|in:a,b,ab,o',
            'marital_status' => 'required|string|in:single,married',
            'religion' => 'required|string|max:255',
            'identity_type' => 'required|string|max:255',
            'identity_numbers' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'citizen_address' => 'required|string|max:255',
            'residential_address' => 'required|string|max:255',
            'basic_salary' => 'required|numeric',
            'payment_type' => 'required|string|in:Monthly, Weekly, Daily',
            'departement_id' => 'required|integer|exists:departements,id',
            'job_position_id' => 'required|integer|exists:job_positions,id',
            'job_level_id' => 'required|integer|exists:job_levels,id',
            'approval_line_id' => 'required|integer|exists:users,id',
            'approval_manager_id' => 'required|integer|exists:users,id',
            'join_date' => 'required|date',
            'sign_date' => 'required|date',
            'resign_date' => 'required|date',
            'bank_name' => 'required|string|max:255',
            'bank_number' => 'required|string|max:255',
            'bank_holder' => 'required|string|max:255',

            // Family and Education validations
            'family' => 'required|array|min:1',
            'family.*.id' => 'required|integer',
            'family.*.fullname' => 'required|string|max:255',
            'family.*.relationship' => 'required|string|max:255',
            'family.*.birthdate' => 'required|date',
            'family.*.marital_status' => 'required|string|in:single,married',
            'family.*.job' => 'required|string|max:255',

            'formal_education' => 'required|array|min:1',
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
}
