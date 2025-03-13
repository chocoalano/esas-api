<?php
namespace App\Models\UserApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserFormalEducation extends Model
{
    protected $table = "user_formal_educations";
    protected $fillable = [
        'user_id',
        'institution',
        'majors',
        'score',
        'start',
        'finish',
        'status',
        'certification',
    ];
    protected $casts = [
        'certification' => 'boolean',
    ];
    public const STATUS = ['passed' => 'Passed', 'not-passed' => 'Not Passed', 'in-progress' => 'In Progress'];
    public const MAJORS = [
        'sd' => 'Sekolah Dasar (SD)',
        'smp' => 'Sekolah Menengah Pertama (SMP)',
        'sma' => 'Sekolah Menengah Atas',
        'smk' => 'Sekolah Menengah Kejuruan',
        'diploma_1' => 'Diploma 1 (D1)',
        'diploma_2' => 'Diploma 2 (D2)',
        'diploma_3' => 'Diploma 3 (D3)',
        'sarjana' => 'Sarjana (S1)',
        'magister' => 'Magister (S2)',
        'doktor' => 'Doktor (S3)',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
