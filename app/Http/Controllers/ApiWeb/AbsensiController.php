<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\AdministrationApp\UserAttendance;
use App\Support\Logger;
use App\Support\UploadFile;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsensiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validasi request
        $validated = $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'required|integer|min:1|max:100',
            'sortBy' => 'nullable|array',
            'sortBy.*.key' => 'required_with:sortBy|string|in:time_in,time_out,date,created_at,updated_at,user.nip,user.name',
            'sortBy.*.order' => 'required_with:sortBy|string|in:asc,desc',

            'search' => 'nullable|array',
            'search.company_id' => 'nullable|integer',
            'search.departement_id' => 'nullable|integer',
            'search.user_id' => 'nullable|integer',
            'search.status_in' => 'nullable|string|in:late,unlate,normal',
            'search.status_out' => 'nullable|string|in:late,unlate,normal',
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.start' => 'nullable|date_format:Y-m-d',
            'search.end' => 'nullable|date_format:Y-m-d|after_or_equal:search.start',
        ]);
        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        $query = UserAttendance::with([
            'user',
            'user.company',
            'user.employee.departement',
            'user.employee.jobPosition',
            'schedule.timework',
            'qrPresenceTransactions'
        ]);
        $query->when(!empty($search['company_id']), function ($q) use ($search) {
            $q->whereHas('user', fn($q) => $q->where('company_id', $search['company_id']));
        });

        $query->when(!empty($search['departement_id']), function ($q) use ($search) {
            $q->whereHas('user.employee', fn($emp) => $emp->where('departement_id', $search['departement_id']));
        });
        $query->when(!empty($search['user_id']), fn($q) => $q->where('user_id', $search['user_id']));
        $query->when(!empty($search['status_in']), fn($q) => $q->where('status_in', $search['status_in']));
        $query->when(!empty($search['status_out']), fn($q) => $q->where('status_out', $search['status_out']));
        $query->when(!empty($search['createdAt']), fn($q) => $q->whereDate('created_at', $search['createdAt']));
        $query->when(!empty($search['updatedAt']), fn($q) => $q->whereDate('updated_at', $search['updatedAt']));
        if (!empty($search['start']) && !empty($search['end'])) {
            $query->whereBetween('updated_at', [$search['start'], $search['end']]);
        }
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                // Jika ingin urut berdasarkan relasi user.name / user.nip
                if (in_array($sort['key'], ['user.name', 'user.nip'])) {
                    $query->whereHas('user', function ($q) use ($sort) {
                        $q->orderBy($sort['key'], $sort['order']);
                    });
                } else {
                    $query->orderBy($sort['key'], $sort['order']);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $data = $query->paginate($limit, ['*'], 'page', $page);
        Logger::log('list paginate', $data->first() ?? new UserAttendance(), $data->toArray());
        return $this->sendResponse($data, 'Data kehadiran berhasil diambil');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi data input
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'user_timework_schedule_id' => ['required', 'exists:user_timework_schedules,id'],
            'time_in' => ['required', 'date_format:H:i:s'],
            'time_out' => ['required', 'date_format:H:i:s'],
            'type_in' => ['required', 'string', 'max:50'],
            'type_out' => ['required', 'string', 'max:50'],
            'lat_in' => ['required', 'numeric', 'between:-90,90'],
            'lat_out' => ['required', 'numeric', 'between:-90,90'],
            'long_in' => ['required', 'numeric', 'between:-180,180'],
            'long_out' => ['required', 'numeric', 'between:-180,180'],
            'image_in' => ['nullable', 'image', 'max:10240'],
            'image_out' => ['nullable', 'image', 'max:10240'],
            'status_in' => ['required', 'in:late,unlate,normal'],
            'status_out' => ['required', 'in:late,unlate,normal'],
        ]);

        try {
            // Inisialisasi model
            $absen = new UserAttendance([
                'user_id' => $validated['user_id'],
                'user_timework_schedule_id' => $validated['user_timework_schedule_id'],
                'time_in' => $validated['time_in'],
                'time_out' => $validated['time_out'],
                'type_in' => $validated['type_in'],
                'type_out' => $validated['type_out'],
                'lat_in' => $validated['lat_in'],
                'lat_out' => $validated['lat_out'],
                'long_in' => $validated['long_in'],
                'long_out' => $validated['long_out'],
                'image_in' => ['nullable', 'image', 'max:10240'],   // Max 10MB
                'image_out' => ['nullable', 'image', 'max:10240'],
                'status_in' => $validated['status_in'],
                'status_out' => $validated['status_out'],
                'created_by' => Auth::id(),
            ]);

            // Upload gambar jika tersedia
            $timestamp = Carbon::now()->format('YmdHis');

            foreach (['image_in', 'image_out'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    $upload = UploadFile::uploadToSpaces($request->file($imageField), 'attendances', $timestamp);
                    if (is_array($upload) && isset($upload['path'])) {
                        $absen->{$imageField} = $upload['path'];
                    }
                }
            }

            $absen->save();
            Logger::log('create', new UserAttendance(), $absen->toArray());
            return $this->sendResponse($absen, 'Data absensi berhasil dibuat.');
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menyimpan data.', [
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            // Menyimpan data absensi ke database
            $dt = UserAttendance::with([
                'user',
                'user.company',
                'user.employee.departement',
                'user.employee.jobPosition',
                'schedule.timework',
                'qrPresenceTransactions'
            ])->find($id);
            Logger::log('show', new UserAttendance(), $dt->toArray());
            return $this->sendResponse($dt, 'Data absensi berhasil dimuat');
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
            'user_id' => ['required', 'exists:users,id'],
            'user_timework_schedule_id' => ['required', 'exists:user_timework_schedules,id'],
            'time_in' => ['required', 'date_format:H:i:s'],
            'time_out' => ['required', 'date_format:H:i:s'],
            'type_in' => ['required', 'string', 'max:50'],
            'type_out' => ['required', 'string', 'max:50'],
            'lat_in' => ['required', 'numeric', 'between:-90,90'],
            'lat_out' => ['required', 'numeric', 'between:-90,90'],
            'long_in' => ['required', 'numeric', 'between:-180,180'],
            'long_out' => ['required', 'numeric', 'between:-180,180'],
            'image_in' => ['nullable', 'image', 'max:10240'],   // Max 10MB
            'image_out' => ['nullable', 'image', 'max:10240'],
            'status_in' => ['required', 'in:late,unlate,normal'],
            'status_out' => ['required', 'in:late,unlate,normal'],
        ]);
        try {
            $absen = UserAttendance::find($id);
            $payload = [
                'before'=>$absen->toArray(),
                'after'=>$validated,
            ];
            $absen->user_id = $validated['user_id'];
            $absen->user_timework_schedule_id = $validated['user_timework_schedule_id'];
            $absen->time_in = $validated['time_in'];
            $absen->time_out = $validated['time_out'];
            $absen->type_in = $validated['type_in'];
            $absen->type_out = $validated['type_out'];
            $absen->lat_in = $validated['lat_in'];
            $absen->lat_out = $validated['lat_out'];
            $absen->long_in = $validated['long_in'];
            $absen->long_out = $validated['long_out'];
            $absen->status_in = $validated['status_in'];
            $absen->status_out = $validated['status_out'];
            $absen->updated_by = auth()->user()->id;

            // Proses upload gambar jika ada
            if ($request->hasFile('image_in')) {
                if ($absen->image_in) {
                    UploadFile::removeFromSpaces($absen->image_in);
                }
                $upload = UploadFile::uploadToSpaces($request->file('image_in'), 'attendances', Carbon::now()->format('YmdHis'));
                if (is_array($upload) && isset($upload['url']) && isset($upload['path'])) {
                    $absen->image_in = $upload['path'];
                }
            }

            if ($request->hasFile('image_out')) {
                if ($absen->image_out) {
                    UploadFile::removeFromSpaces($absen->image_out);
                }
                $upload = UploadFile::uploadToSpaces($request->file('image_out'), 'attendances', Carbon::now()->format('YmdHis'));
                if (is_array($upload) && isset($upload['url']) && isset($upload['path'])) {
                    $absen->image_out = $upload['path'];
                }
            }
            $absen->save();
            Logger::log('update', new UserAttendance(), $payload);
            return $this->sendResponse($absen, 'Data absensi berhasil diperbaharui.');
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
            $dt = UserAttendance::whereIn('id', $idData);
            $delete = $dt->get();
            Logger::log('delete', new UserAttendance(), $delete->toArray());
            $dt->delete();
            return $this->sendResponse($dt, 'Data absensi berhasil dihapus');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */

    public function downloadpdf(Request $request)
    {
        try {
            // Naikkan batas memori agar DOMPDF tidak kehabisan memori
            ini_set('memory_limit', '512M');

            // Validasi input
            $validated = $request->validate([
                'company_id' => 'nullable|integer|exists:companies,id',
                'departement_id' => 'nullable|integer|exists:departements,id',
                'user_id' => 'nullable|integer|exists:users,id',
                'status_in' => 'nullable|string|in:late,unlate,normal',
                'status_out' => 'nullable|string|in:late,unlate,normal',
                'start' => 'required|date|before_or_equal:end',
                'end' => 'required|date|after_or_equal:start',
            ]);

            // Query absensi dengan relasi yang dibutuhkan
            $query = UserAttendance::with([
                'user',
                'user.company',
                'user.employee.departement',
                'user.employee.jobPosition',
                'schedule.timework',
                'qrPresenceTransactions'
            ]);

            // Filter berdasarkan input
            $query->when($validated['company_id'] ?? null, function ($q, $companyId) {
                $q->whereHas('user', fn($q) => $q->where('company_id', $companyId));
            });

            $query->when($validated['departement_id'] ?? null, function ($q, $deptId) {
                $q->whereHas('user.employee', fn($q) => $q->where('departement_id', $deptId));
            });

            $query->when($validated['user_id'] ?? null, fn($q, $userId) => $q->where('user_id', $userId));
            $query->when($validated['status_in'] ?? null, fn($q, $statusIn) => $q->where('status_in', $statusIn));
            $query->when($validated['status_out'] ?? null, fn($q, $statusOut) => $q->where('status_out', $statusOut));

            $query->whereBetween('created_at', [$validated['start'], $validated['end']]);

            // Batasi data agar tidak membebani memori
            $data = $query->limit(500)->get(); // Sesuaikan limit sesuai kebutuhan

            if ($data->isEmpty()) {
                return response()->json(['message' => 'Tidak ada data absensi yang ditemukan.'], 404);
            }

            // Generate PDF
            $pdf = Pdf::loadView('pdf.absensi', [
                'absensi' => $data,
                'start' => $validated['start'],
                'end' => $validated['end']
            ]);

            $filename = 'absensi-' . now()->format('Ymd_His') . '.pdf';
            Logger::log('pdf download', new UserAttendance(), $data->toArray());
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghasilkan PDF.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadExcel(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'departement_id' => 'nullable|integer|exists:departements,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'status_in' => 'nullable|string|in:late,unlate,normal',
            'status_out' => 'nullable|string|in:late,unlate,normal',
            'start' => 'required|date|before_or_equal:end',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $dates = collect(CarbonPeriod::create($validated['start'], $validated['end']));

        $query = DB::table('users AS u')
            ->select([
                'u.nip AS employee_id',
                'u.name AS first_name',
                'd.name AS departement',
                'jp.name AS position',
                'jl.name AS level',
                'ue.join_date',
            ])
            ->addSelect($dates->map(function ($date) {
                $formatted = $date->format('Y-m-d');
                return DB::raw("
                    MAX(CASE
                        WHEN DATE(ua.created_at) = '{$formatted}'
                        THEN CONCAT(COALESCE(ua.time_in, '-'), ' - ', COALESCE(ua.time_out, '-'))
                        ELSE NULL
                    END) AS `{$formatted}`");
            })->toArray())
            ->addSelect([
                DB::raw("SEC_TO_TIME(SUM(
                    CASE
                        WHEN ua.time_in IS NOT NULL AND tw.in IS NOT NULL AND ua.time_in > tw.in
                        THEN TIME_TO_SEC(TIMEDIFF(ua.time_in, tw.in))
                        ELSE 0
                    END
                )) AS total_jam_terlambat"),

                DB::raw("SUM(CASE WHEN pt.type IN (
                    'Dispensasi Menikah', 'Dispensasi menikahkan anak',
                    'Dispensasi khitan/baptis anak', 'Dispensasi Keluarga/Anggota Keluarga Dalam Satu Rumah Meninggal',
                    'Dispensasi Melahirkan/Keguguran', 'Dispensasi Ibadah Agama',
                    'Dispensasi Wisuda (anak/pribadi)', 'Dispensasi Lain-lain',
                    'Dispensasi Tugas Kantor (dalam/luar kota)'
                ) THEN 1 ELSE 0 END) AS dispensasi"),

                DB::raw("SUM(CASE WHEN pt.type IN (
                    'Izin Sakit (surat dokter & resep)', 'Izin Sakit (tanpa surat dokter)',
                    'Izin Sakit Kecelakaan Kerja (surat dokter & resep)', 'Izin Sakit (rawat inap)',
                    'Izin Koreksi Absen', 'izin perubahan jam kerja'
                ) THEN 1 ELSE 0 END) AS izin"),

                DB::raw("SUM(CASE WHEN pt.type IN (
                    'Cuti Tahunan', 'Unpaid Leave (Cuti Tidak Dibayar)'
                ) THEN 1 ELSE 0 END) AS cuti"),
            ])
            ->join('user_employes AS ue', 'u.id', '=', 'ue.user_id')
            ->join('departements AS d', 'ue.departement_id', '=', 'd.id')
            ->join('job_positions AS jp', 'ue.job_position_id', '=', 'jp.id')
            ->join('job_levels AS jl', 'ue.job_level_id', '=', 'jl.id')
            ->leftJoin('user_attendances AS ua', function ($join) use ($validated) {
                $join->on('ua.user_id', '=', 'u.id')
                    ->whereBetween(DB::raw('DATE(ua.created_at)'), [$validated['start'], $validated['end']]);
            })
            ->leftJoin('permits AS p', 'p.user_id', '=', 'u.id')
            ->leftJoin('permit_types AS pt', 'p.permit_type_id', '=', 'pt.id')
            ->leftJoin('user_timework_schedules AS uts', 'uts.id', '=', 'ua.user_timework_schedule_id')
            ->leftJoin('time_workes AS tw', 'uts.time_work_id', '=', 'tw.id')
            ->groupBy([
                'u.id',
                'u.nip',
                'u.name',
                'd.name',
                'jp.name',
                'jl.name',
                'ue.join_date',
                'ue.sign_date',
                'ue.resign_date'
            ])
            ->orderBy('u.name', 'ASC');

        // Gunakan when + Arr::wrap untuk filter fleksibel
        $query->when($validated['company_id'] ?? null, fn($q, $v) => $q->where('u.company_id', $v));
        $query->when($validated['departement_id'] ?? null, fn($q, $v) => $q->where('d.id', $v));
        $query->when($validated['user_id'] ?? null, fn($q, $v) => $q->where('u.id', $v));
        $query->when($validated['status_in'] ?? null, fn($q, $v) => $q->where('ua.status_in', $v));
        $query->when($validated['status_out'] ?? null, fn($q, $v) => $q->where('ua.status_out', $v));

        $data = $query->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Header
        $headers = array_keys((array) $data[0]); // Ambil key dari array pertama
        $columnIndex = 'A';

        foreach ($headers as $header) {
            $sheet->setCellValue($columnIndex . '1', strtoupper($header));
            $columnIndex++;
        }

        // Isi Data
        $rowNumber = 2;
        foreach ($data as $row) {
            $columnIndex = 'A';
            foreach ((array) $row as $value) {
                $sheet->setCellValue($columnIndex . $rowNumber, $value);
                $columnIndex++;
            }
            $rowNumber++;
        }

        // Buat stream response
        $fileName = 'data_export_attendance_' . now()->format('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        Logger::log('xlsx download', new UserAttendance(), $data->toArray());
        return $response;
    }
}
