<?php

namespace App\Models\CoreApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;
    protected $table = 'settings';
    protected $fillable = [
        'company_id',
        'attendance_image_geolocation',
        'attendance_qrcode',
        'attendance_fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'attendance_image_geolocation' => 'boolean',
            'attendance_qrcode' => 'boolean',
            'attendance_fingerprint' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
