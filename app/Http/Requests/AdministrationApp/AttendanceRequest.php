<?php

namespace App\Http\Requests\AdministrationApp;

class AttendanceRequest
{
    public static function presence(): array
    {
        return [
            'time_id' => ['required', 'integer', 'exists:time_workes,id'],
            'time' => [
                'required',
                'date_format:H:i:s'
            ],
            'lat' => [
                'required',
                'numeric',
                'regex:/^(\-?\d+(\.\d+)?)$/',
                'between:-90,90',
            ],
            'long' => [
                'required',
                'numeric',
                'regex:/^(\-?\d+(\.\d+)?)$/',
                'between:-180,180',
            ],
            'type' => [
                'required',
                'string',
                'in:in,out',
            ],
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg'
            ],
        ];
    }
}
