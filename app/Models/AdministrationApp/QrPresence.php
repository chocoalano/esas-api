<?php

namespace App\Models\AdministrationApp;

use App\Models\CoreApp\Departement;
use App\Models\CoreApp\TimeWork;
use Illuminate\Database\Eloquent\Model;

class QrPresence extends Model
{
    protected $table = "qr_presences";
    protected $fillable = [
        'type',
        'departement_id',
        'timework_id',
        'token',
        'for_presence',
        'expires_at',
    ];

    public const ENUM_TYPE = [
        'in' => 'IN',
        'out' => 'OUT',
    ];

    protected $casts = [
        'for_presence' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasOne(QrPresenceTransaction::class, 'qr_presence_id');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'id');
    }
    public function timework()
    {
        return $this->belongsTo(TimeWork::class, 'id');
    }
}
