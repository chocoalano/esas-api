<?php

namespace App\Models\Tools;

use App\Models\User;
use App\Models\CoreApp\Company;
use Illuminate\Database\Eloquent\Model;

class BugReport extends Model
{
    protected $table = "bug_reports";
    protected $fillable = [
        "company_id",
        "user_id",
        "title",
        "status",
        "message",
        "platform",
        "image",
    ];

    public const PLATFORM = [
        'web' => 'web',
        'android' => 'android',
        'ios' => 'ios'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
