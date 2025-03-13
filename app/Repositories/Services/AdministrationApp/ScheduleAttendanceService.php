<?php
namespace App\Repositories\Services\AdministrationApp;

use App\Jobs\InsertUpdateScheduleJob;
use App\Models\AdministrationApp\UserTimeworkSchedule;
use App\Repositories\Interfaces\AdministrationApp\ScheduleAttendanceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScheduleAttendanceService implements ScheduleAttendanceInterface
{
    protected $model;

    public function __construct(UserTimeworkSchedule $model)
    {
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function import(array $data)
    {
        $jadwal = [];
        // Gunakan DB Transaction untuk memastikan semua proses berhasil atau dibatalkan
        foreach ($data as $k) {
            if (
                isset($k['company'], $k['nip'], $k['name'], $k['department'], $k['shift_name'], $k['shift_date']) ||
                !empty($k['company']) || !empty($k['nip']) || !empty($k['name']) ||
                !empty($k['department']) || !empty($k['shift_name']) || !empty($k['shift_date'])
            ) {
                // Temukan user berdasarkan NIP
                $user = DB::table('users')
                    ->join('user_employes', 'users.id', '=', 'user_employes.user_id')
                    ->join('companies', 'users.company_id', '=', 'companies.id')
                    ->where('users.nip', $k['nip'])
                    ->where('companies.name', $k['company'])
                    ->select('users.company_id', 'users.id as user_id', 'user_employes.departement_id')
                    ->first();
                if (!$user || !$user->company_id || !$user->departement_id) {
                    // Jika user tidak ditemukan, lewati data ini
                    continue;
                }
                // Temukan TimeWork berdasarkan nama shift dan department ID
                $time = DB::table('departements')
                    ->join('time_workes', 'departements.id', '=', 'time_workes.departemen_id')
                    ->join('companies', 'time_workes.company_id', '=', 'companies.id')
                    ->where('departements.id', $user->departement_id)
                    ->where('companies.id', $user->company_id)
                    ->where('time_workes.name', 'LIKE', '%' . $k['shift_name'] . '%')
                    ->select('departements.*', 'time_workes.*', 'companies.*')
                    ->first();
                if (!$time) {
                    // Jika TimeWork tidak ditemukan, lewati data ini
                    continue;
                }
                // Tambahkan jadwal untuk user
                $jadwal[] = [
                    'user_id' => $user->user_id,
                    'time_work_id' => $time->id,
                    'work_day' => $k['shift_date'],
                ];
            }
            continue;
        }
        // Masukkan jadwal ke dalam database
        // Dispatch job jika diperlukan
        if (!empty($jadwal)) {
            InsertUpdateScheduleJob::dispatch($jadwal);
        }
        return true;
    }
    /**
     * @inheritDoc
     */
    public function template(int $company, int $departement)
    {
        $user = Auth::user();
        $spreadsheet = new Spreadsheet();

        // Sheet utama
        $mainSheet = $spreadsheet->getActiveSheet();
        $mainSheet->setTitle('Main Data');

        // Header untuk sheet utama
        $headers = [
            'A' => 'Company',
            'B' => 'NIP',        // Akan menjadi select option
            'C' => 'Name',
            'D' => 'Department',
            'E' => 'Shift Name', // Akan menjadi select option
            'F' => 'Shift Date',
        ];

        // Mengisi header
        foreach ($headers as $column => $header) {
            $mainSheet->setCellValue("{$column}1", $header);
        }

        // Filter tanggal bulan ini
        $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

        // Query untuk mengambil data utama
        $dataTemplateQuery = DB::table('users as u')
            ->join('companies as c', 'c.id', '=', 'u.company_id')
            ->join('user_employes as ue', 'ue.user_id', '=', 'u.id')
            ->join('departements as d', 'd.id', '=', 'ue.departement_id')
            ->join('user_timework_schedules as uts', 'uts.user_id', '=', 'u.id')
            ->join('time_workes as tw', 'uts.time_work_id', '=', 'tw.id')
            ->select([
                'c.name as company_name',
                'u.nip as user_nip',
                'u.name as user_name',
                'd.name as department_name',
                'tw.name as shift_name',
                'uts.work_day as schedule_date',
            ])
            ->whereBetween('uts.work_day', [$currentMonthStart, $currentMonthEnd]);

        // Filter berdasarkan role
        if ($user->hasRole(['Admin Departement', 'Member'])) {
            $dataTemplateQuery->where('c.id', $company)
                ->where('d.id', $departement);
        }

        // Eksekusi query
        $dataTemplate = $dataTemplateQuery->get();


        // Isi data ke sheet utama
        if ($dataTemplate->isNotEmpty()) {
            $row = 2;
            foreach ($dataTemplate as $data) {
                $mainSheet->setCellValue("A{$row}", $data->company_name);
                $mainSheet->setCellValue("B{$row}", $data->user_nip);
                $mainSheet->setCellValue("C{$row}", $data->user_name);
                $mainSheet->setCellValue("D{$row}", $data->department_name);
                $mainSheet->setCellValue("E{$row}", $data->shift_name);
                $mainSheet->setCellValue("F{$row}", $data->schedule_date);
                $row++;
            }
        }

        // Buat sheet Shift Options
        $shiftSheet = $spreadsheet->createSheet();
        $shiftSheet->setTitle('Shift Options');
        $shiftSheet->setCellValue('A1', 'Shift Name');
        $shiftSheet->setCellValue('B1', 'Departement');
        $shiftSheet->setCellValue('C1', 'Company');

        // Ambil data shift
        $shiftQuery = DB::table('time_workes')
        ->join('companies', 'companies.id', '=', 'time_workes.company_id')
        ->join('departements', 'departements.id', '=', 'time_workes.departemen_id')
        ->select('time_workes.name', 'companies.name as company', 'departements.name as departemen');
        if ($user->hasRole(['Admin Departement', 'Member'])) {
            $shiftQuery
            ->where('time_workes.company_id', $company)
            ->where('time_workes.departemen_id', $departement);
        }
        $shifts = $shiftQuery->get();
        if ($shifts->isNotEmpty()) {
            $row = 2;
            foreach ($shifts as $shift) {
                $shiftSheet->setCellValue("A{$row}", $shift->name);
                $shiftSheet->setCellValue("B{$row}", $shift->departemen);
                $shiftSheet->setCellValue("C{$row}", $shift->company);
                $row++;
            }

            // Tambahkan Named Range untuk Shift Options
            $spreadsheet->addNamedRange(
                new \PhpOffice\PhpSpreadsheet\NamedRange(
                    'ShiftOptions',
                    $shiftSheet,
                    "\$A\$2:\$A\$" . ($row - 1)
                )
            );
        }

        // Buat sheet Users
        $userSheet = $spreadsheet->createSheet();
        $userSheet->setTitle('Users');
        $userSheet->setCellValue('A1', 'NIP');
        $userSheet->setCellValue('B1', 'Name');
        $userSheet->setCellValue('C1', 'Departement');
        $userSheet->setCellValue('D1', 'Company');

        // Ambil data user
        $userQuery = DB::table('users')
            ->join('user_employes', 'user_employes.user_id', '=', 'users.id')
            ->join('departements', 'departements.id', '=', 'user_employes.departement_id')
            ->join('companies', 'companies.id', '=', 'users.company_id')
            ->select(['users.nip', 'users.name', 'departements.name as departemen', 'companies.name as company']);
        if ($user->hasRole(['Admin Departement', 'Member'])) {
            $userQuery->where('users.company_id', $user->company_id)
                ->where('users.company_id', $user->company_id)
                ->where('user_employes.departement_id', $user->employee->departement_id);
        }
        $users = $userQuery->get();

        if ($users->isNotEmpty()) {
            $row = 2;
            foreach ($users as $userData) {
                $userSheet->setCellValue("A{$row}", $userData->nip);
                $userSheet->setCellValue("B{$row}", $userData->name);
                $userSheet->setCellValue("C{$row}", $userData->departemen);
                $userSheet->setCellValue("D{$row}", $userData->company);
                $row++;
            }

            // Tambahkan Named Range untuk User Options
            $spreadsheet->addNamedRange(
                new \PhpOffice\PhpSpreadsheet\NamedRange(
                    'NIPOptions',
                    $userSheet,
                    "\$A\$2:\$A\$" . ($row - 1)
                )
            );
        }

        // Set dropdown untuk kolom NIP
        $highestRow = $mainSheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $validation = $mainSheet->getCell("B{$row}")->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1('NIPOptions');
        }

        // Nama file untuk download
        $fileName = 'template-import-schedule-' . Carbon::now()->format('YmdHis') . '.xlsx';
        $filePath = public_path($fileName);
        $writer = new Xlsx($spreadsheet);

        // Simpan file dan kembalikan response untuk download
        $writer->save($filePath);
        return response()->download($filePath)->deleteFileAfterSend();
    }

    /**
     * @inheritDoc
     */
    public function time_validation(int $scheduleId, int $userId, string $timeInOrOut, string $currentTime): string
    {
        $find = $this->model->with('timework')
            ->where('id', $scheduleId)
            ->where('user_id', $userId)
            ->first();
        if (!$find || !$find->timework) {
            throw new \Exception("Schedule or Timework not found.");
        }
        $timework = $find->timework;
        return match ($timeInOrOut) {
            'in' => $timework->in > $currentTime ? 'unlate' : 'late',
            'out' => $timework->out > $currentTime ? 'late' : 'normal',
            default => throw new \InvalidArgumentException("Invalid timeInOrOut value: $timeInOrOut"),
        };
    }
    /**
     * @inheritDoc
     */
    public function find(int $id)
    {
        return $this->model->with(
            'user',
            'timework',
            'employee',
            'company'
        )
            ->find($id);
    }
    /**
     * @inheritDoc
     */
    public function update(int $id, array $data)
    {
        $find = $this->model->find($id);
        $find->update($data);
        return $find;
    }
}
