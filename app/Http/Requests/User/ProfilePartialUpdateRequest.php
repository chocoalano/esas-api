<?php

namespace App\Http\Requests\User;

use Illuminate\Support\Facades\Auth;

class ProfilePartialUpdateRequest
{
    public static function family(): array
    {
        return [
            // Family and Education validations
            '*.id' => 'required|integer',
            '*.fullname' => 'required|string|max:255',
            '*.relationship' => 'required|string|in:wife,husband,mother,father,brother,sister,child',
            '*.birthdate' => 'required|date',
            '*.marital_status' => 'required|string|in:single,married,widow,widower',
            '*.job' => 'required|string|max:255',
        ];
    }
    public static function formal_education(): array
    {
        return [
            '*.id' => 'required|integer',
            '*.institution' => 'required|string|max:255',
            '*.majors' => 'required|string|max:255',
            '*.score' => 'required|numeric',
            '*.start' => [
                'required',
                'regex:/^\d{4}$/',
                'before_or_equal:*.finish',
            ],
            '*.finish' => [
                'required',
                'regex:/^\d{4}$/',
                'after_or_equal:*.start',
            ],
            '*.status' => 'required|string|in:passed, not-passed, in-progress',
            '*.certification' => 'required|boolean',
        ];
    }
    public static function informal_education()
    {
        return [
            '*.id' => 'required|integer',
            '*.institution' => 'required|string|max:255',
            '*.start' => [
                'required',
                'regex:/^\d{4}$/',
                'before_or_equal:*.finish',
            ],
            '*.finish' => [
                'required',
                'regex:/^\d{4}$/',
                'after_or_equal:*.start',
            ],
            '*.type' => 'required|string|in:day, year, month',
            '*.duration' => 'required|numeric',
            '*.status' => 'required|string|in:passed, not-passed, in-progress',
            '*.certification' => 'required|boolean',
        ];
    }
    public static function work_experience()
    {
        return [
            '*.id' => 'required|integer',
            '*.company_name' => 'required|string|max:255',
            '*.start' => [
                'required',
                'regex:/^\d{4}$/',
                'before_or_equal:*.finish',
            ],
            '*.finish' => [
                'required',
                'regex:/^\d{4}$/',
                'after_or_equal:*.start',
            ],
            '*.position' => 'required|string|max:255',
            '*.certification' => 'required|boolean',
        ];
    }
    public static function update_password()
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'different:password', // Pastikan password baru berbeda dari password lama
            ],
            'confirmation_new_password' => [
                'required',
                'string',
                'min:8',
                'same:new_password', // Pastikan konfirmasi password baru cocok dengan password baru
            ],
        ];
    }
    public static function update_bank($userId)
    {
        return [
            'bank_name' => [
                'required',
                'string',
                'min:3',
            ],
            'bank_number' => [
                'required',
                'numeric',
                "unique:user_employes,bank_number,$userId",
                'min:8',
            ],
            'bank_holder' => [
                'required',
                'string',
                'min:2',
            ],
        ];
    }
}
