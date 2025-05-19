<?php
namespace App\Repositories\Services\AdministrationApp;

use App\FcmNotification\PermitNotification;
use App\Models\AdministrationApp\Permit;
use App\Models\AdministrationApp\PermitType;
use App\Repositories\Interfaces\AdministrationApp\AttendanceInterface;
use App\Repositories\Interfaces\AdministrationApp\PermitInterface;
use App\Repositories\Interfaces\AdministrationApp\ScheduleAttendanceInterface;
use App\Repositories\Interfaces\CoreApp\UserInterface;
use App\Support\NotificationService;
use App\Support\StringSupport;
use App\Support\UploadFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PermitService implements PermitInterface
{
    protected $model;
    protected $type;
    protected $notif;

    public function __construct(Permit $model, PermitType $type, PermitNotification $notif)
    {
        $this->model = $model;
        $this->type = $type;
        $this->notif = $notif;
    }

    /**
     * @inheritDoc
     */
    public function generate_unique_numbers(int $permit_type_id)
    {
        $permit_type = PermitType::find($permit_type_id);

        if (!$permit_type) {
            return;
        }

        // Membuat inisial 3 huruf
        $inisial = StringSupport::inisial($permit_type->type, 3);

        // Mendapatkan tahun dan bulan
        $tahun_bulan = Carbon::now()->format('Ym');

        // Mencari nomor urut terakhir berdasarkan pola
        $lastRecord = $this->model->where('permit_numbers', 'like', "$inisial/$tahun_bulan/%")
            ->orderBy('permit_numbers', 'desc')
            ->first();

        // Menentukan nomor urut berikutnya
        $lastNumber = 0;
        if ($lastRecord) {
            $lastReference = explode('/', $lastRecord->permit_numbers);
            $lastNumber = intval(end($lastReference));
        }
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        // Membuat nomor referensi baru
        $reference = "$inisial/$tahun_bulan/$nextNumber";

        // Debugging atau menyimpan nilai
        return $reference;
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

                return ['labels' => ['No Data'], 'total' => []];
        }

        // Ensure valid date range is set
        if (!$startDate || !$endDate) {
            return ['labels' => $labels, 'total' => []];
        }

        // Fetch grouped data from the database
        $groupedData = DB::table('permits as p')
            ->selectRaw('COUNT(*) as total, DATE(p.created_at) as date')
            ->whereBetween('p.created_at', [$startDate, $endDate])
            ->groupByRaw('DATE(p.created_at)')
            ->get()
            ->groupBy('DATE(p.created_at)');

        // Initialize datasets
        $total = array_fill(0, count($labels), 0);

        // Map grouped data to the respective dataset
        foreach ($groupedData as $status => $data) {
            foreach ($data as $entry) {
                $labelIndex = array_search($entry->date, $labels);
                if ($labelIndex !== false) {
                    $total[$labelIndex] = match ($status) {
                        'total' => $entry->total,
                    };
                }
            }
        }

        return [
            'labels' => $labels,
            'total' => $total,
        ];
    }
    /**
     * @inheritDoc
     */
    public function all()
    {
        return $this->model->with(
            'user',
            'permitType',
            'approvals',
            'userTimeworkSchedule'
        )->all();
    }

    /**
     * @inheritDoc
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['file']) && !empty($data['file'])) {
                $attachment=UploadFile::uploadToSpaces($data['file'], 'permits', now()->format('YmdHis'));
                $data['file'] = $attachment['path'];
            }
            // Buat permit
            $permit = $this->model->create($data);

            // Ambil tipe permit
            $permitType = PermitType::find($data['permit_type_id']);
            if (!$permitType) {
                throw new \Exception("Permit type not found.");
            }

            // Siapkan approval array
            $approval = [];

            // Ambil user dan HR terkait
            $user = app(UserInterface::class)->find($data['user_id']);
            if (!$user) {
                throw new \Exception("User not found.");
            }

            $userHrList = app(UserInterface::class)->findUserHr()->toArray();
            $authorizedHr = collect($userHrList)->firstWhere('nip', '24020001');
            if (!$authorizedHr) {
                throw new \Exception("Authorized HR not found.");
            }

            // Tambahkan approval berdasarkan permit type
            if ($permitType->approve_line) {
                $approval[] = [
                    'user_id' => $user->employee->approval_line_id,
                    'user_type' => 'line',
                ];
            }
            if ($permitType->approve_manager) {
                $approval[] = [
                    'user_id' => $user->employee->approval_manager_id,
                    'user_type' => 'manager',
                ];
            }
            if ($permitType->approve_hr) {
                $approval[] = [
                    'user_id' => $authorizedHr['id'],
                    'user_type' => 'hrga',
                ];
            }

            // Simpan approval
            if (!empty($approval)) {
                $permit->approvals()->createMany($approval);
            }
            $userIds = [];
            foreach ($approval as $k) {
             array_push($userIds, $k['user_id']);
            }
            $this->notif->broadcast_approvals($userIds, "{$user->name}-{$user->nip}", $permitType->type);
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        return $this->find($id)->delete();
    }

    /**
     * @inheritDoc
     */
    public function find(int $id)
    {
        return $this->model->with(
            'user',
            'permitType',
            'approvals',
            'userTimeworkSchedule'
        )->find($id);
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        $find = $this->find($id);
        $find->update($data);
        return $find;
    }
    /**
     * @inheritDoc
     */
    public function approved(int $permitId, int $authId, string $approve, string $notes): bool
    {
        $find = $this->model->with(['approvals', 'permitType', 'userTimeworkSchedule'])->find($permitId);

        if (!$find) {
            return false; // Permit tidak ditemukan
        }

        // Update approval status
        $filtered = array_values(array_filter($find->approvals->toArray(), function ($item) use ($authId) {
            return $item['user_id'] === (int) $authId;
        }));

        // Ambil elemen pertama dari hasil filter
        $result = $filtered ?? null;
        foreach ($result as $k) {
            $find->approvals()
                ->where(['id' => $k['id'], 'user_id' => $k['user_id']])
                ->update([
                    'user_approve' => $approve,
                    'notes' => $notes,
                ]);
        }

        // Cek jika semua approval telah disetujui
        if ($approve === 'y') {
            $allApproved = $find->approvals->every(fn($approval) => $approval['user_approve'] === 'y');

            if ($allApproved) {
                $type = $find->permitType->type;

                switch ($type) {
                    case 'izin koreksi absen':
                        $this->handleAttendanceCorrection($find);
                        break;

                    case 'izin perubahan jam kerja':
                        $this->handleChangeTimeWorkSchedule($find);
                        break;

                    case 'cuti tahunan':
                        $this->handleAnnualLeave($find);
                        break;
                }
            }
        }
        $user = app(UserInterface::class)->find($find->user_id);
        $url = route('filament.app.resources.permits.index');
        $message = "Halo, saya (" . Auth::user()->name . " - " . Auth::user()->nip . ") mengkonfirmasi permintaan " . $find->permitType->type . " milik " . $user->name . " - " . $user->nip . ", silakan periksa sekarang!";
        NotificationService::sendNotification('Informasi penindakan permintaan', $message, $url, $find->user_id);
        foreach ($find->approvals->toArray() as $k) {
            NotificationService::sendNotification('Informasi penindakan permintaan', $message, $url, $k['user_id']);
        }
        $this->notif->broadcast_user_apply($user->id, Auth::user()->name." - ".Auth::user()->nip, $find->permitType->type);
        return true; // Proses berhasil
    }

    public function handleAttendanceCorrection($permit)
    {
        $attendance = app(AttendanceInterface::class)
            ->findbySchedule($permit->userTimeworkSchedule->id);
        app(AttendanceInterface::class)->update(
            $attendance->id,
            [
                'time_in' => $permit->timein_adjust,
                'time_out' => $permit->timeout_adjust,
                'status_in' => app(ScheduleAttendanceInterface::class)
                    ->time_validation($permit->userTimeworkSchedule->id, $permit->user_id, 'in', $permit->timein_adjust),
                'status_out' => app(ScheduleAttendanceInterface::class)
                    ->time_validation($permit->userTimeworkSchedule->id, $permit->user_id, 'out', $permit->timeout_adjust),
            ]
        );
    }

    public function handleChangeTimeWorkSchedule($permit)
    {
        app(ScheduleAttendanceInterface::class)->update(
            $permit->userTimeworkSchedule->id,
            [
                'time_work_id' => $permit->adjust_shift_id
            ]
        );
    }

    public function handleAnnualLeave($permit)
    {
        $user = app(UserInterface::class)->find($permit->user_id);
        $start = Carbon::createFromFormat('Y-m-d', $permit->start_date);
        $end = Carbon::createFromFormat('Y-m-d', $permit->end_date);
        $total_hari = $start->diffInDays($end);
        $user->employee->update([
            'saldo_cuti' => (int) $user->employee->saldo_cuti - (int) $total_hari
        ]);
    }
    /**
     * @inheritDoc
     */
    public function paginate(int $page, int $limit, string $search = null, int $type)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole(['super_admin', 'Administrator']);
        // Eager load necessary relationships
        $query = $this->model->with([
            'user',
            'permitType',
            'approvals',
            'userTimeworkSchedule',
        ]);
        // Filter by permit type
        $query->where('permit_type_id', $type);
        // Apply department filtering for non-admin users
        if (!$isAdmin) {
            $userDepartmentId = optional($user->employee)->departement_id; // Safely access employee's department_id
            if ($userDepartmentId) {
                $query->whereHas('user.employee', function ($q) use ($userDepartmentId) {
                    $q->where('departement_id', $userDepartmentId);
                });
            }
        }
        // Apply search filtering
        if (!empty($search)) {
            $query->where(function ($q) use ($search, $user) {
                $q->whereHas('user', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', '%' . $search . '%');
                })->orWhereHas('approvals', function ($subQuery) use ($search, $user) {
                    $subQuery->where('user_id', $user->id)
                        ->where('user_approve', 'LIKE', '%' . $search . '%');
                });
            });
        }
        // Order results by creation date
        $query->orderByDesc('created_at');
        // Return paginated results
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * @inheritDoc
     */
    public function type()
    {
        return $this->type->where('show_mobile', true)->get();
    }
}
