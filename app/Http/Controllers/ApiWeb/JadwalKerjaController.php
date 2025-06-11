<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Jobs\InsertUpdateScheduleJob;
use App\Models\AdministrationApp\UserTimeworkSchedule;
use App\Support\Logger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JadwalKerjaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'required|integer|min:1|max:100',
            'sortBy' => 'nullable|array',
            'sortBy.*.key' => 'required_with:sortBy|string|in:name,latitude,longitude,radius,full_address,created_at,updated_at',
            'sortBy.*.order' => 'required_with:sortBy|string|in:asc,desc',
            'search' => 'nullable|array',
            'search.company_id' => 'nullable|integer',
            'search.departement_id' => 'nullable|integer',
            'search.timework_id' => 'nullable|integer',
            'search.workday' => 'nullable|date_format:Y-m-d',
            'search.user_id' => 'nullable|integer',
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.startRange' => 'nullable|date_format:Y-m-d',
            'search.endRange' => 'nullable|date_format:Y-m-d',
        ]);
        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        $query = UserTimeworkSchedule::with([
            'user',
            'timework',
            'employee.departement',
            'user.company'
        ]);
        // Filtering
        if (!empty($search['company_id'])) {
            $query->whereHas('user', function ($user) use ($search) {
                $user->where('company_id', $search['company_id']);
            });
        }
        if (!empty($search['departement_id'])) {
            $query->whereHas('user', function ($user) use ($search) {
                $user->whereHas('employee', function ($emp) use ($search) {
                    $emp->where('departement_id', $search['departement_id']);
                });
            });
        }
        if (!empty($search['timework_id'])) {
            $query->whereHas('timework', function ($time) use ($search) {
                $time->where('id', $search['timework_id']);
            });
        }
        if (!empty($search['workday'])) {
            $query->whereDate('work_day', $search['workday']);
        }
        if (!empty($search['createdAt'])) {
            $query->whereDate('created_at', $search['createdAt']);
        }
        if (!empty($search['updatedAt'])) {
            $query->whereDate('updated_at', $search['updatedAt']);
        }
        if (!empty($search['startRange']) && !empty($search['endRange'])) {
            $query->whereBetween('updated_at', [$search['startRange'], $search['endRange']]);
        }
        // Sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        // Pagination
        $data = $query->paginate($limit, ['*'], 'page', $page);
        if (!auth()->user()->hasRole('super_admin')) {
            Logger::log('list paginate', new UserTimeworkSchedule(), $data->toArray());
        }
        return $this->sendResponse($data, 'Data laporan bug berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'departement' => 'required|integer|exists:departements,id',
            'user_id' => 'required|array|min:1',
            'user_id.*' => 'integer|exists:users,id',
            'time_work_id' => 'required|integer|exists:time_workes,id',
            'work_day_start' => 'required|date|before_or_equal:work_day_finish',
            'work_day_finish' => 'required|date|after_or_equal:work_day_start',
            'dayoff' => 'nullable|array',
            'dayoff.*' => 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        ]);
        try {
            // Konversi ke timezone lokal
            $workDayStart = Carbon::parse($validated['work_day_start'])->timezone(config('app.timezone'));
            $workDayFinish = Carbon::parse($validated['work_day_finish'])->timezone(config('app.timezone'));
            // Validasi ulang tanggal (jaga-jaga)
            if ($workDayStart->greaterThan($workDayFinish)) {
                throw new \Exception('Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
            }
            $jadwal = [];
            $skipDays = $validated['dayoff'] ?? [];
            while ($workDayStart->lte($workDayFinish)) {
                $dayName = $workDayStart->format('l');
                if (!in_array($dayName, $skipDays)) {
                    foreach ($validated['user_id'] as $userId) {
                        $jadwal[] = [
                            'user_id' => $userId,
                            'time_work_id' => $validated['time_work_id'],
                            'work_day' => $workDayStart->toDateString(),
                        ];
                    }
                }
                $workDayStart->addDay();
            }
            if (!empty($jadwal)) {
                // Gunakan dispatch jika ingin background job
                InsertUpdateScheduleJob::dispatch($jadwal);
                return $this->sendResponse($validated, 'Data jadwal kerja berhasil dibuat.');
            }
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('create', new UserTimeworkSchedule(), $jadwal);
            }
            return $this->sendError('Terjadi kesalahan saat menyimpan data.', [
                'error' => "Jumlah data yang valid: " . count($jadwal)
            ], 500);
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menyimpan data.', [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            // Menyimpan data laporan bug ke database
            $dt = UserTimeworkSchedule::with([
                'company',
                'user'
            ])->find($id);
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('show', $dt ?? new UserTimeworkSchedule(), $dt->toArray());
            }
            return $this->sendResponse($dt, 'Data laporan bug berhasil dimuat');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
            'departement' => 'required|integer|exists:departements,id',
            'user_id' => 'required|array|min:1',
            'user_id.*' => 'integer|exists:users,id',
            'time_work_id' => 'required|integer|exists:time_workes,id',
            'work_day_start' => 'required|date|before_or_equal:work_day_finish',
            'work_day_finish' => 'required|date|after_or_equal:work_day_start',
            'dayoff' => 'nullable|array',
            'dayoff.*' => 'in:Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
        ]);
        try {
            return $this->sendResponse($validated, 'Data laporan bug berhasil diperbaharui');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $idData = explode(',', $id);
        try {
            $dt = UserTimeworkSchedule::whereIn('id', $idData);
            $delete = $dt->get();
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('delete', $dt ?? new UserTimeworkSchedule(), $delete->toArray());
            }
            $dt->delete();
            return $this->sendResponse($dt, 'Data laporan bug berhasil dihapus');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */

    public function downloadpdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        // Validasi input
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'departement_id' => 'required|exists:departements,id',
            'timework_id' => 'required|exists:time_workes,id',
            'workday' => 'nullable|date',
            'user_id' => 'required|exists:users,id',
        ]);
        // Build query berdasarkan input yang tervalidasi
        $query = UserTimeworkSchedule::with([
            'user',
            'user.company',
            'user.employee.departement',
            'timeWork',
        ]);
        if (!empty($validated['company_id'])) {
            $query->whereHas('user', function ($u) use ($validated) {
                $u->where('company_id', $validated['company_id']);
            });
        }
        if (!empty($validated['departement_id'])) {
            $query->whereHas('user', function ($d) use ($validated) {
                $d->whereHas('employee', function ($dept) use ($validated) {
                    $dept->where('departement_id', $validated['departement_id']);
                });
            });
        }
        if (!empty($validated['timework_id'])) {
            $query->whereHas('timework', function ($t) use ($validated) {
                $t->where('id', $validated['timework_id']);
            });
        }
        if (!empty($validated['workday'])) {
            $query->where('work_day', $validated['workday']);
        }
        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }
        // Ambil data hasil filter
        $companies = $query->get();
        // Jika tidak ada data, bisa kasih fallback (opsional)
        if ($companies->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data laporan bug yang ditemukan.'], 404);
        }
        // // Generate PDF
        $pdf = Pdf::loadView('pdf.jadwal-kerja', ['jadwalkerja' => $companies]);
        $filename = 'jadwal-kerja-' . now()->format('YmdHis') . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadExcel(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'radius' => ['nullable', 'numeric', 'min:0'],
            'createdAt' => ['nullable', 'date_format:Y-m-d'],
            'updatedAt' => ['nullable', 'date_format:Y-m-d'],
            'startRange' => ['nullable', 'date_format:Y-m-d'],
            'endRange' => ['nullable', 'date_format:Y-m-d'],
        ]);

        // Build query berdasarkan input yang tervalidasi
        $query = UserTimeworkSchedule::query();

        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }
        if (!empty($validated['radius'])) {
            $query->where('radius', $validated['radius']);
        }
        if (!empty($validated['createdAt'])) {
            $query->whereDate('created_at', $validated['createdAt']);
        }
        if (!empty($validated['updatedAt'])) {
            $query->whereDate('updated_at', $validated['updatedAt']);
        }
        if (!empty($validated['startRange']) && !empty($validated['endRange'])) {
            $query->whereBetween('created_at', [
                $validated['startRange'],
                $validated['endRange']
            ]);
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data laporan bug yang ditemukan.'], 404);
        }

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama');
        $sheet->setCellValue('C1', 'Radius');
        $sheet->setCellValue('D1', 'Dibuat');
        $sheet->setCellValue('E1', 'Diperbarui');

        // Isi data
        $row = 2;
        foreach ($companies as $i => $dt) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $dt->name);
            $sheet->setCellValue('C' . $row, $dt->radius);
            $sheet->setCellValue('D' . $row, $dt->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('E' . $row, $dt->updated_at->format('Y-m-d H:i:s'));
            $row++;
        }

        // Buat stream response
        $fileName = 'data_departemen_' . now()->format('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
