<?php

namespace App\Http\Requests\Tools;

class BugReportRequest
{
    public static function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:255',
            'platform' => 'required|in:web,android,ios',
            'image' => 'required|mimes:jpeg,jpg,png|max:10000',
        ];
    }
}
