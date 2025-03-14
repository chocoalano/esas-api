<?php
namespace App\Repositories\Services\AdministrationApp;

use App\Models\AdministrationApp\QrPresenceTransaction;
use App\Models\AdministrationApp\UserAttendance;
use App\Models\views\AttendanceView;
use App\Repositories\Interfaces\AdministrationApp\AttendanceInterface;
use App\Support\StringSupport;
use App\Support\UploadFile;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;

class AttendanceService implements AttendanceInterface
{
    protected $model;

    public function __construct(UserAttendance $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function auth_all(int $month)
    {
        $monthName = Carbon::create()->month($month)->format('m');
        $auth = Auth::user();

        $cacheKey = "attendance.auth_all.{$auth->id}.{$auth->company_id}.{$monthName}";
        $cacheTTL = now()->addMinutes(1); // Cache valid for 10 minutes

        return Cache::remember($cacheKey, $cacheTTL, function () use ($auth, $monthName) {
            return AttendanceView::where([
                'user_id' => $auth->id,
                'company_id' => $auth->company_id,
            ])
                ->whereMonth('created_at', $monthName)
                ->whereYear('created_at', Carbon::now()->format('Y'))
                ->get();
        });
    }

    /**
     * @inheritDoc
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        $find = $this->model->find($id);
        if ($find) {
            $find->delete();
        }
        return $find ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function find(int $id)
    {
        return $this->model->find($id);
    }
    public function findbySchedule(int $id)
    {
        return $this->model->whereHas('schedule', function ($q) use ($id) {
            $q->where('id', $id);
        })->first();
    }

    /**
     * @inheritDoc
     */
    public function paginate(int $page, int $limit, ?string $search = null)
    {
        $auth_user = Auth::user();

        $query = AttendanceView::query()
            ->where('user_id', $auth_user->id)
            ->where('company_id', $auth_user->company_id);

        // Tambahkan filter pencarian jika ada
        if (!empty($search)) {
            $query->whereMonth('created_at', $search)
                ->whereYear('created_at', Carbon::now()->year);
        }

        // Pastikan query tidak menggunakan cache
        $query->whereRaw('NOW() IS NOT NULL');

        return $query->orderByDesc('id')
            ->paginate($limit, ['*'], 'page', $page);
    }


    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        $find = $this->model->findOrFail($id);
        if ($find) {
            $find->update($data);
        }
        return $find;
    }

    /**
     * @inheritDoc
     */
    public function presence_qr($userId, $type, $idtoken, $token)
    {
        if (!in_array($type, ['in', 'out'])) {
            throw new Exception('Tipe absen tidak valid!');
        }

        $currentTime = Carbon::now()->setTimezone(config('app.timezone'));
        $currentDate = Carbon::today()->setTimezone(config('app.timezone'));

        // Ambil data QR Presence + departemen + waktu kerja
        $qrPresence = DB::table('qr_presences as qrp')
            ->join('departements as d', 'qrp.departement_id', '=', 'd.id')
            ->join('time_workes as tw', 'qrp.timework_id', '=', 'tw.id')
            ->select([
                'qrp.id',
                'qrp.type',
                'qrp.token',
                'qrp.expires_at',
                'qrp.departement_id',
                'qrp.timework_id',
                'd.name as departement_name',
                'tw.in as work_start_time',
                'tw.company_id'
            ])
            ->where([
                ['qrp.type', '=', $type],
                ['qrp.id', '=', $idtoken]
            ])
            ->first();
        if (!$qrPresence) {
            throw new Exception('Token tidak ditemukan!');
        }

        // Cek apakah QR sudah digunakan
        if (DB::table('qr_presence_transactions')->where('qr_presence_id', $qrPresence->id)->exists()) {
            throw new Exception('Kode QR sudah digunakan!');
        }

        // Cek apakah QR sudah expired
        $exp = Carbon::parse($qrPresence->expires_at)
            ->setTimezone(config("app.timezone"));
        if ($currentTime->gt($exp)) {
            throw new Exception('Kode QR sudah kadaluarsa!');
        }

        // Cek apakah user terdaftar di departemen QR
        $isUserInDepartment = DB::table('users')
            ->where('id', $userId)
            ->whereExists(function ($query) use ($qrPresence) {
                $query->select(DB::raw(1))
                    ->from('user_employes')
                    ->whereColumn('user_employes.user_id', 'users.id')
                    ->where('user_employes.departement_id', $qrPresence->departement_id);
            })
            ->exists();

        if (!$isUserInDepartment) {
            throw new Exception('User tidak terdaftar di departemen ini.');
        }

        // Ambil jadwal kerja user (jika ada)
        $scheduleId = DB::table('user_timework_schedules')
            ->where('user_id', $userId)
            ->where('work_day', $currentDate)
            ->value('id');

        // Jika absen keluar, pastikan sudah ada absen masuk
        if (
            $type === 'out' && !DB::table('user_attendances')
                ->where('user_id', $userId)
                ->whereDate('created_at', $currentDate)
                ->whereNotNull('time_in')
                ->exists()
        ) {
            throw new Exception('Anda harus melakukan absensi masuk sebelum absensi pulang!');
        }

        // Hitung status keterlambatan
        $status = $currentTime->lt($qrPresence->work_start_time) ? 'normal' : 'late';
        $statusInOut = ($type === 'in') ? $status : ($currentTime->lt($qrPresence->work_start_time) ? 'unlate' : 'normal');

        // Ambil lokasi perusahaan
        $company = DB::table('companies')
            ->where('id', $qrPresence->company_id)
            ->select('latitude', 'longitude')
            ->first();

        $proses_data = DB::transaction(function () use ($userId, $currentTime, $currentDate, $type, $qrPresence, $scheduleId, $statusInOut, $company) {
            // Cek apakah user sudah memiliki absen hari ini
            $attendance = UserAttendance::where('user_id', $userId)
                ->whereDate('created_at', $currentDate)
                ->first();

            if ($attendance) {
                // Update absen jika sudah ada
                $attendance->update([
                    'updated_at' => $currentTime,
                    'time_in' => $type === 'in' ? $currentTime : $attendance->time_in,
                    'status_in' => $type === 'in' ? $statusInOut : $attendance->status_in,
                    'lat_in' => $type === 'in' ? $company->latitude : $attendance->lat_in,
                    'long_in' => $type === 'in' ? $company->longitude : $attendance->long_in,
                    'type_in' => $type === 'in' ? 'qrcode' : $attendance->type_in,
                    'created_by' => $type === 'in' ? $userId : $userId,
                    'time_out' => $type === 'out' ? $currentTime : $attendance->time_out,
                    'status_out' => $type === 'out' ? $statusInOut : ($attendance->status_out ?? 'normal'),
                    'lat_out' => $type === 'out' ? $company->latitude : $attendance->lat_out,
                    'long_out' => $type === 'out' ? $company->longitude : $attendance->long_out,
                    'type_out' => $type === 'out' ? 'qrcode' : $attendance->type_out,
                    'updated_by' => $type === 'out' ? $userId : null,
                ]);
            } else {
                // Buat absen baru jika belum ada
                UserAttendance::create([
                    'user_id' => $userId,
                    'user_timework_schedule_id' => $scheduleId,
                    'created_at' => $currentTime,
                    'updated_at' => $currentTime,
                    'time_in' => $type === 'in' ? $currentTime : null,
                    'status_in' => $type === 'in' ? $statusInOut : 'normal',
                    'lat_in' => $type === 'in' ? $company->latitude : null,
                    'long_in' => $type === 'in' ? $company->longitude : null,
                    'type_in' => $type === 'in' ? 'qrcode' : null,
                    'created_by' => $type === 'in' ? $userId : null,
                    'time_out' => $type === 'out' ? $currentTime : null,
                    'status_out' => $type === 'out' ? $statusInOut : 'normal',
                    'lat_out' => $type === 'out' ? $company->latitude : null,
                    'long_out' => $type === 'out' ? $company->longitude : null,
                    'type_out' => $type === 'out' ? 'qrcode' : null,
                    'updated_by' => $type === 'out' ? $userId : null,
                ]);
            }

            // Simpan transaksi QR
            QrPresenceTransaction::create([
                'qr_presence_id' => $qrPresence->id,
                'user_attendance_id' => $attendance->id ?? DB::getPdo()->lastInsertId(), // Ambil ID terakhir jika baru dibuat
                'token' => $qrPresence->token,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);
        });

        return $proses_data;
    }

    /**
     * @inheritDoc
     */
    public function presence_in(array $data)
    {
        $requiredFields = ['time_id', 'lat', 'long', 'type', 'image', 'time'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        $user = Auth::user();
        $attendance = $this->model->where([
            'user_id' => $user->id,
            'time_in' => $data['time'],
        ])
            ->whereDate('created_at', Carbon::now()->format('Y-m-d'))
            ->first();
        if ($attendance) {
            UploadFile::unlink($attendance->image_in);
        }
        $upload = UploadFile::uploadWithResize($data['image'], 'attendance-in');
        $exec = DB::select(
            'CALL UpdateAttendanceIn(?,?,?,?,?,?)',
            [
                $user->id,
                $data['time_id'],
                $data['lat'],
                $data['long'],
                $upload,
                $data['time']
            ]
        );
        return $exec[0]->success === 1 ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function presence_out(array $data)
    {
        $requiredFields = ['time_id', 'lat', 'long', 'type', 'image', 'time'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        $user = Auth::user();
        $today = Carbon::now()->format('Y-m-d'); // Menggunakan variabel untuk format tanggal yang sama

        // Mendapatkan attendance berdasarkan user dan tanggal yang relevan
        $attendance = $this->model->where('user_id', $user->id)
            ->whereNotNull('time_in')
            ->whereDate('created_at', $today)
            ->first();

        if (!$attendance) {
            return false; // Attendance tidak ditemukan, langsung return false
        }

        // Hapus gambar lama jika ada
        if ($attendance->image_out !== null || !empty($attendance->image_out)) {
            UploadFile::unlink($attendance->image_out);
        }

        // Upload gambar baru dengan resize
        $upload = UploadFile::uploadWithResize($data['image'], 'attendance-out');
        $exec = DB::select(
            'CALL UpdateAttendanceOut(?,?,?,?,?,?)',
            [
                $user->id,
                $data['time_id'],
                $data['lat'],
                $data['long'],
                $upload,
                $data['time']
            ]
        );
        return $exec[0]->success === 1 ? true : false;
    }

    /**
     * @inheritDoc
     */
    public function countAll()
    {
        return $this->model->count();
    }
    /**
     * @inheritDoc
     */
    public function chart(string $filter)
    {
        $startDate = null;
        $endDate = null;
        $labels = [];

        // Determine the date range and labels based on the filter
        switch ($filter) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today();
                $labels = [$startDate->format('Y-m-d')];
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                $labels = StringSupport::generateDateLabels($startDate, $endDate);
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                $labels = StringSupport::generateDateLabels($startDate, $endDate);
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                // Generate month labels
                $labels = [
                    'Jan',
                    'Feb',
                    'Mar',
                    'Apr',
                    'May',
                    'Jun',
                    'Jul',
                    'Aug',
                    'Sep',
                    'Oct',
                    'Nov',
                    'Dec'
                ];
                break;
            default:
                return ['labels' => ['No Data'], 'late' => [], 'unlate' => [], 'normal' => []];
        }

        // Ensure valid date range is set
        if (!$startDate || !$endDate) {
            return ['labels' => $labels, 'late' => [], 'unlate' => [], 'normal' => []];
        }

        // Format the start and end dates for better database comparison
        $startDateFormatted = $startDate->format('Y-m-d');
        $endDateFormatted = $endDate->format('Y-m-d');

        // Cache key generation based on filter and date range
        $cacheKey = "attendance_chart_{$filter}_{$startDateFormatted}_{$endDateFormatted}";

        // Try to get the data from cache
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            // Return cached data if available
            return $cachedData;
        }

        // Fetch grouped data from the database
        $groupedData = DB::table('user_attendances as ua')
            ->selectRaw('COUNT(*) as total, DATE(ua.created_at) as date, ua.status_in')
            ->whereBetween(DB::raw('DATE(ua.created_at)'), [$startDateFormatted, $endDateFormatted])
            ->groupBy(DB::raw('DATE(ua.created_at)'), 'ua.status_in')
            ->get();

        // Initialize datasets for late, unlate, and normal statuses
        $late = array_fill(0, count($labels), 0);
        $unlate = array_fill(0, count($labels), 0);
        $normal = array_fill(0, count($labels), 0);

        // Map grouped data to the respective dataset
        foreach ($groupedData as $data) {
            // Find the correct label index based on the date format
            $labelIndex = array_search($data->date, $labels);

            if ($labelIndex !== false) {
                // Store data based on status_in value
                switch ($data->status_in) {
                    case 'late':
                        $late[$labelIndex] = $data->total;
                        break;
                    case 'unlate':
                        $unlate[$labelIndex] = $data->total;
                        break;
                    case 'normal':
                        $normal[$labelIndex] = $data->total;
                        break;
                }
            }
        }

        // Data to be cached
        $dataToCache = [
            'labels' => $labels,
            'late' => $late,
            'unlate' => $unlate,
            'normal' => $normal,
        ];

        // Store the result in cache for 60 minutes (or customize duration as needed)
        Cache::put($cacheKey, $dataToCache, 60);

        // Return the structured data
        return $dataToCache;
    }

    public function correction(UserAttendance $record, array $data)
    {
        return DB::transaction(function () use ($record, $data) {
            $att = UserAttendance::findOrFail($record->id);

            $cek = DB::table('permits as p')
                ->join('permit_types as pt', 'p.permit_type_id', '=', 'pt.id')
                ->join('permit_approves as pa', 'p.id', '=', 'pa.permit_id')
                ->where('p.permit_numbers', $data['no_permit'])
                ->where('p.user_id', $att->user_id)
                ->where('pt.type', $data['correction'])
                ->selectRaw('
                    SUM(CASE WHEN pa.user_approve = "y" THEN 1 ELSE 0 END) AS approve_total,
                    SUM(CASE WHEN pa.user_approve = "n" THEN 1 ELSE 0 END) AS rejected_total,
                    SUM(CASE WHEN pa.user_approve = "w" THEN 1 ELSE 0 END) AS waiting_total,
                    p.*
                ')
                ->groupBy('p.id')
                ->first();

            if (!$cek) {
                throw new Exception('Invalid Correction Numbers');
            }

            if ($cek->rejected_total >= 1 || $cek->waiting_total >= 1) {
                throw new Exception('Correction request is either rejected or still waiting for approval.');
            }

            if ($data['correction'] === 'Izin Koreksi Absen') {
                $att->time_in = $cek->timein_adjust;
                $att->time_out = $cek->timeout_adjust;
                $att->status_in = 'normal';
                $att->status_out = 'normal';
            }

            if ($data['correction'] === 'izin perubahan jam kerja') {
                if (!$att->user_timework_schedule_id) {
                    throw new Exception('Correction request cannot have schedule before!');
                } else {
                    $att->schedule->update(['time_work_id', $cek->adjust_shift_id]);
                }
            }

            return $att->save();
        }, 1);
    }

    /**
     * @inheritDoc
     */
    public function report($startDate, $endDate)
    {
        $query = DB::table('users AS u')
            ->select([
                'u.nip AS employee_id',
                'u.name AS first_name',
                'd.name AS departement',
                'jp.name AS position',
                'jl.name AS level',
                'ue.join_date',
                DB::raw("SUM(CASE WHEN pt.type IN (
                'Dispensasi Menikah', 
                'Dispensasi menikahkan anak',
                'Dispensasi khitan/baptis anak', 
                'Dispensasi Keluarga/Anggota Keluarga Dalam Satu Rumah Meninggal',
                'Dispensasi Melahirkan/Keguguran', 
                'Dispensasi Ibadah Agama',
                'Dispensasi Wisuda (anak/pribadi)', 
                'Dispensasi Lain-lain',
                'Dispensasi Tugas Kantor (dalam/luar kota)'
            ) THEN 1 ELSE 0 END) AS dispensasi"),
                DB::raw("SUM(CASE WHEN pt.type IN (
                'Izin Sakit (surat dokter & resep)', 
                'Izin Sakit (tanpa surat dokter)',
                'Izin Sakit Kecelakaan Kerja (surat dokter & resep)', 
                'Izin Sakit (rawat inap)',
                'Izin Koreksi Absen', 
                'izin perubahan jam kerja'
            ) THEN 1 ELSE 0 END) AS izin"),
                DB::raw("SUM(CASE WHEN pt.type IN ('Cuti Tahunan', 'Unpaid Leave (Cuti Tidak Dibayar)') THEN 1 ELSE 0 END) AS cuti"),
            ])
            ->addSelect(collect(CarbonPeriod::create($startDate, $endDate))->map(function ($date) {
                $formattedDate = $date->format('Y-m-d');
                return DB::raw("MAX(CASE WHEN DATE(ua.created_at) = '$formattedDate' THEN CONCAT(ua.time_in, ' - ', ua.time_out) ELSE NULL END) AS `$formattedDate`");
            })->toArray())
            ->join('user_employes AS ue', 'u.id', '=', 'ue.user_id')
            ->join('departements AS d', 'ue.departement_id', '=', 'd.id')
            ->join('job_positions AS jp', 'ue.job_position_id', '=', 'jp.id')
            ->join('job_levels AS jl', 'ue.job_level_id', '=', 'jl.id')
            ->leftJoin('user_attendances AS ua', function ($join) use ($startDate, $endDate) {
                $join->on('ua.user_id', '=', 'u.id')
                    ->whereBetween(DB::raw('DATE(ua.created_at)'), [$startDate, $endDate]);
            })
            ->leftJoin('permits AS p', 'p.user_id', '=', 'u.id')
            ->leftJoin('permit_types AS pt', 'p.permit_type_id', '=', 'pt.id')
            ->groupBy(
                'u.id',
                'u.nip',
                'u.name',
                'd.name',
                'jp.name',
                'jl.name',
                'ue.join_date',
                'ue.sign_date',
                'ue.resign_date'
            )
            ->orderBy('u.name', 'ASC')
            ->get();

        return $query;
    }

}
