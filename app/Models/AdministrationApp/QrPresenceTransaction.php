<?php

namespace App\Models\AdministrationApp;

use Illuminate\Database\Eloquent\Model;

class QrPresenceTransaction extends Model
{
    protected $table = "qr_presence_transactions";
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'qr_presence_id',
        'user_attendance_id',
        'token',
    ];

    /**
     * Relationship with QrPresence.
     * Each transaction belongs to a specific QR presence.
     */
    public function qrPresence()
    {
        return $this->belongsTo(QrPresence::class, 'qr_presence_id');
    }

    /**
     * Relationship with UserAttendance.
     * Each transaction is associated with a specific user attendance.
     */
    public function userAttendance()
    {
        return $this->belongsTo(UserAttendance::class, 'user_attendance_id');
    }

    /**
     * Check if the transaction token matches a given token.
     */
    public function isValidToken(string $token): bool
    {
        return $this->token === $token;
    }
}
