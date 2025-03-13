<?php

namespace App\Http\Requests\AdministrationApp;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PermitRequest
{
    public static function koreksi_absen(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permit_type_id' => ['required', 'integer', 'exists:permit_types,id'],
            'permit_numbers' => [
                'required',
                'string',
                'max:255',
                'unique:permits,permit_numbers'
            ],
            'user_timework_schedule_id' => ['required', 'integer', 'exists:user_timework_schedules,id'],
            'timein_adjust' => ['required', 'date_format:H:i:s', 'before:timeout_adjust'],
            'timeout_adjust' => ['required', 'date_format:H:i:s', 'after:timein_adjust'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => 'nullable|mimes:jpeg,jpg,png,pdf|max:10000',
        ];
    }
    public static function perubahan_shift(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permit_type_id' => ['required', 'integer', 'exists:permit_types,id'],
            'permit_numbers' => [
                'required',
                'string',
                'max:255',
                'unique:permits,permit_numbers'
            ],
            'user_timework_schedule_id' => ['required', 'integer', 'exists:user_timework_schedules,id'],
            'current_shift_id' => ['required', 'integer', 'exists:time_workes,id'],
            'adjust_shift_id' => ['required', 'integer', 'exists:time_workes,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => 'nullable|mimes:jpeg,jpg,png,pdf|max:10000',
        ];
    }
    public static function lainya(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permit_type_id' => ['required', 'integer', 'exists:permit_types,id'],
            'permit_numbers' => [
                'required',
                'string',
                'max:255',
                'unique:permits,permit_numbers'
            ],
            'user_timework_schedule_id' => ['required', 'integer', 'exists:user_timework_schedules,id'],
            'start_date' => [
                'required',
                'date',
                'before_or_equal:end_date',
                function ($attribute, $value, $fail) {
                    $schedule = DB::table('user_timework_schedules')
                        ->where('id', request('user_timework_schedule_id'))
                        ->value('work_day');

                    if (!$schedule || $schedule !== $value) {
                        $fail("The $attribute must match the work day in the selected schedule.");
                    }
                },
            ],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i:s', 'before:end_time'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => 'nullable|mimes:jpeg,jpg,png,pdf|max:10000',
        ];
    }
}
