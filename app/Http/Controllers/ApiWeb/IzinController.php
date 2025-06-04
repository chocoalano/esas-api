<?php

namespace App\Http\Controllers\ApiWeb;

use App\FcmNotification\PermitNotification;
use App\Http\Controllers\Controller;
use App\Models\AdministrationApp\Permit;
use App\Models\AdministrationApp\PermitType;
use App\Repositories\Interfaces\AdministrationApp\PermitInterface;
use App\Support\UploadFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IzinController extends Controller
{
    protected $proses;
    protected $notif;

    public function __construct(PermitInterface $proses, PermitNotification $notif)
    {
        $this->proses = $proses;
        $this->notif = $notif;
        $this->middleware('auth');
        $this->middleware('permission:view_permit|view_any_permit', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_permit', ['only' => ['store']]);
        $this->middleware('permission:update_permit', ['only' => ['update']]);
        $this->middleware('permission:delete_permit|delete_any_permit', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'page' => 'required|integer|min:1',
            'limit' => 'required|integer|min:1|max:100',
            'sortBy' => 'nullable|array',
            'sortBy.*.key' => 'required_with:sortBy|string|in:name,latitude,longitude,radius,full_address,created_at,updated_at',
            'sortBy.*.order' => 'required_with:sortBy|string|in:asc,desc',
            'search' => 'sometimes|array',
            'search.permit_type' => 'nullable|integer|exists:permit_types,id',
            'search.permit_numbers' => 'nullable|string|max:100',
            'search.workday' => 'nullable|date',
            'search.createdAt' => 'nullable|date',
            'search.updatedAt' => 'nullable|date',
        ]);

        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        $query = Permit::query();
        $query->with([
            'user',
            'permitType',
            'approvals',
            'userTimeworkSchedule',
        ]);
        // Filtering
        if (!empty($search)) {
            if (!empty($search['permit_numbers'])) {
                $query->where('permit_numbers', 'like', '%' . $search['permit_numbers'] . '%');
            }
            if (!empty($search['permit_type'])) {
                $query->where('permit_type_id', $search['permit_type']);
            }
            if (!empty($search['workday'])) {
                $query->whereHas('userTimeworkSchedule', function ($tw) use ($search) {
                    $tw->where('workday', $search['workday']);
                });
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
        $companies = $query->paginate($limit, ['*'], 'page', $page);
        return $this->sendResponse($companies, 'Data izin berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function codeNumbers(Request $request)
    {
        $permit = $request->input('permit_type_id');
        $number = $this->proses->generate_unique_numbers($permit);
        return $this->sendResponse($number, 'Data izin berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge([
            'timein_adjust' => $request->timein_adjust === 'null' ? null : $request->timein_adjust,
            'timeout_adjust' => $request->timeout_adjust === 'null' ? null : $request->timeout_adjust,
            'current_shift_id' => $request->current_shift_id === 'null' ? null : $request->current_shift_id,
            'adjust_shift_id' => $request->adjust_shift_id === 'null' ? null : $request->adjust_shift_id,
        ]);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'departement_id' => 'required|exists:departements,id',
            'user_id' => 'required|exists:users,id',
            'permittype_id' => 'required|exists:permit_types,id',
            'schedule_id' => 'required|exists:user_timework_schedules,id',
            'permit_numbers' => 'required|string|max:50|unique:permits,permit_numbers',
            'timein_adjust' => ['nullable', 'date_format:H:i'],
            'timeout_adjust' => ['nullable', 'date_format:H:i', 'after:timein_adjust'],
            'current_shift_id' => 'nullable|exists:time_workes,id',
            'adjust_shift_id' => 'nullable|exists:time_workes,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $permit = new Permit();
            $permit->user_id = $validated['user_id'];
            $permit->permit_numbers = $validated['permit_numbers'];
            $permit->permit_type_id = $validated['permittype_id'];
            $permit->user_timework_schedule_id = $validated['schedule_id'];
            $permit->timein_adjust = $validated['timein_adjust'] ?? null;
            $permit->timeout_adjust = $validated['timeout_adjust'] ?? null;
            $permit->current_shift_id = $validated['current_shift_id'] ?? null;
            $permit->adjust_shift_id = $validated['adjust_shift_id'] ?? null;
            $permit->start_date = $validated['start_date'];
            $permit->end_date = $validated['end_date'];
            $permit->start_time = $validated['start_time'];
            $permit->end_time = $validated['end_time'];
            $permit->notes = $validated['notes'] ?? null;
            if ($request->hasFile('file')) {
                $upload = UploadFile::uploadToSpaces($request->file('file'), 'permits', now()->format('YmdHis'));
                if (is_array($upload) && isset($upload['path'])) {
                    $permit->file = $upload['path'];
                }
            }
            $permit->save();
            $permitType = PermitType::find($validated['permittype_id']);
            if (!$permitType) {
                throw new \Exception("Permit type not found.");
            }
            $user = app('App\Repositories\Interfaces\CoreApp\UserInterface'::class)->find($validated['user_id']);
            if (!$user) {
                throw new \Exception("User not found.");
            }
            $userHrList = app('App\Repositories\Interfaces\CoreApp\UserInterface'::class)->findUserHr()->toArray();
            $authorizedHr = collect($userHrList)->firstWhere('nip', '24020001');
            if (!$authorizedHr) {
                throw new \Exception("Authorized HR not found.");
            }
            $approval = [];
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
            if (!empty($approval)) {
                $permit->approvals()->createMany($approval);
            }
            $userIds = array_column($approval, 'user_id');
            $this->notif->broadcast_approvals($userIds, "{$user->name}-{$user->nip}", $permitType->type);
            DB::commit();
            return $this->sendResponse($permit, 'Data izin berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permit Store Error: ' . $e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return $this->sendError('Terjadi kesalahan saat menyimpan data.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            // Menyimpan data izin ke database
            $dt = Permit::with([
                'user.employee',
                'permitType',
                'approvals',
                'userTimeworkSchedule',
            ])->find($id);
            return $this->sendResponse($dt, 'Data izin berhasil dimuat');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->merge([
            'timein_adjust' => $request->timein_adjust === 'null' ? null : $request->timein_adjust,
            'timeout_adjust' => $request->timeout_adjust === 'null' ? null : $request->timeout_adjust,
            'current_shift_id' => $request->current_shift_id === 'null' ? null : $request->current_shift_id,
            'adjust_shift_id' => $request->adjust_shift_id === 'null' ? null : $request->adjust_shift_id,
        ]);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'departement_id' => 'required|exists:departements,id',
            'user_id' => 'required|exists:users,id',
            'permittype_id' => 'required|exists:permit_types,id',
            'permit_numbers' => 'required|string|max:50|unique:permits,permit_numbers,' . $id,
            'timein_adjust' => 'nullable|date_format:H:i',
            'timeout_adjust' => 'nullable|date_format:H:i|after:timein_adjust',
            'current_shift_id' => 'nullable|exists:time_workes,id',
            'adjust_shift_id' => 'nullable|exists:time_workes,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:255',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);
        try {
            // Menyimpan data izin ke database
            $dt = Permit::find($id);
            $dt->user_id = $validated['user_id'];
            $dt->permit_numbers = $validated['permit_numbers'];
            $dt->permit_type_id = $validated['permittype_id'];
            $dt->timein_adjust = $validated['timein_adjust'] ?? null;
            $dt->timeout_adjust = $validated['timeout_adjust'] ?? null;
            $dt->current_shift_id = $validated['current_shift_id'] ?? null;
            $dt->adjust_shift_id = $validated['adjust_shift_id'] ?? null;
            $dt->start_date = $validated['start_date'];
            $dt->end_date = $validated['end_date'];
            $dt->start_time = $validated['start_time'];
            $dt->end_time = $validated['end_time'];
            $dt->notes = $validated['notes'] ?? null;

            if ($request->hasFile('file')) {
                UploadFile::removeFromSpaces($dt->file);
                $upload = UploadFile::uploadToSpaces($request->file('file'), 'permits', now()->format('YmdHis'));
                if (is_array($upload) && isset($upload['path'])) {
                    $dt->file = $upload['path'];
                }
            }

            $dt->save();
            return $this->sendResponse($dt, 'Data izin berhasil diperbaharui');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function approval(Request $request, string $id)
    {
        $validated = $request->validate([
            'approval' => 'required|in:y,n,w',
            'type' => 'required|in:line,manager,hr',
            'notes' => 'nullable|string',
        ]);
        try {
            $permit = Permit::find($id);
            if (!$permit) {
                return $this->sendError('Data izin tidak ditemukan.', [], 404);
            }
            $approval = $permit->approvals()
                ->where('user_id', Auth::id())
                ->where('user_type', $validated['type'] === 'hrga' ? 'hr' : $validated['type'])
                ->first();
            if (!$approval) {
                return $this->sendError('Data approval tidak ditemukan.', [], 404);
            }
            $approval->update([
                'user_approve' => $validated['approval'],
                'notes' => $validated['notes'],
            ]);
            return $this->sendResponse($validated, 'Data approval izin berhasil diperbaharui.');
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan pada proses.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $idData = explode(',', $id);
        try {
            $dt = Permit::whereIn('id', $idData)->delete();
            return $this->sendResponse($dt, 'Data izin berhasil dihapus');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */

    public function downloadpdf(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'createdAt' => ['nullable', 'date_format:Y-m-d'],
            'updatedAt' => ['nullable', 'date_format:Y-m-d'],
            'startRange' => ['nullable', 'date_format:Y-m-d'],
            'endRange' => ['nullable', 'date_format:Y-m-d'],
        ]);
        // Build query berdasarkan input yang tervalidasi
        $query = Permit::with([
            'user',
            'permitType',
            'userTimeworkSchedule',
        ]);
        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
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
        // Ambil data hasil filter
        $data = $query->get();
        // Jika tidak ada data, bisa kasih fallback (opsional)
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data izin yang ditemukan.'], 404);
        }
        // // Generate PDF
        $pdf = Pdf::loadView('pdf.izin', ['izin' => $data]);
        $filename = 'izin-' . now()->format('YmdHis') . '.pdf';

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
        $query = Permit::query();

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
            return response()->json(['message' => 'Tidak ada data izin yang ditemukan.'], 404);
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
