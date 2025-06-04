<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\CoreApp\Company;
use App\Models\CoreApp\Departement;
use App\Models\CoreApp\TimeWork;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Support\UploadFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenggunaController extends Controller
{
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
            'search' => 'nullable|array',
            'search.name' => 'nullable|string',
            'search.company' => 'nullable|integer',
            'search.departemen' => 'nullable|integer',
            'search.position' => 'nullable|integer',
            'search.level' => 'nullable|integer',
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.startRange' => 'nullable|date_format:Y-m-d',
            'search.endRange' => 'nullable|date_format:Y-m-d',
        ]);

        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];

        $query = User::query()->with([
            'company',
            'details',
            'employee',
            'employee.departement',
            'employee.jobPosition',
            'employee.jobLevel',
        ]);

        // Filtering by name (type)
        if (!empty($search['name'])) {
            $query->where('type', 'like', '%' . $search['name'] . '%');
        }

        // Filter by company directly
        if (!empty($search['company'])) {
            $query->where('company_id', $search['company']);
        }

        // Filter employee relationship (departemen, position, level)
        if (!empty($search['departemen']) || !empty($search['position']) || !empty($search['level'])) {
            $query->whereHas('employee', function ($q) use ($search) {
                if (!empty($search['departemen'])) {
                    $q->where('departement_id', $search['departemen']);
                }
                if (!empty($search['position'])) {
                    $q->where('job_position_id', $search['position']);
                }
                if (!empty($search['level'])) {
                    $q->where('job_level_id', $search['level']);
                }
            });
        }

        // Date filters
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
        $result = $query->paginate($limit, ['*'], 'page', $page);

        return $this->sendResponse($result, 'Data User berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateUser($request);
        try {
            $user = User::new();
            $user->company_id = $validated['company_id'];
            $user->name = $validated['name'];
            $user->nip = $validated['nip'];
            $user->email = $validated['email'];
            $user->status = $validated['status'];
            // Update avatar jika ada
            if ($request->hasFile('avatar')) {
                $upload = UploadFile::uploadToSpaces($request->file('avatar'), 'avatars', Carbon::now()->format('YmdHis'));
                if (is_array($upload) && isset($upload['url']) && isset($upload['path'])) {
                    $user->avatar = $upload['path'];
                }
            }
            $user->save();
            $user->details()->updateOrCreate([], $validated['details']);
            $user->address()->updateOrCreate([], $validated['address']);
            $user->salaries()->updateOrCreate([], $validated['salaries']);
            $user->employee()->updateOrCreate([], $validated['employee']);

            return $this->sendResponse($user, 'Data User berhasil dibuat.');
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat menyimpan data.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            // Menyimpan data User ke database
            $dt = User::with([
                'company',
                'details',
                'address',
                'salaries',
                'families',
                'formalEducations',
                'informalEducations',
                'workExperiences',
                'employee',
                'employee.approval_line',
                'employee.approval_manager',
                'employee.departement',
                'employee.jobPosition',
                'employee.jobLevel'
            ])->find($id);
            $cp = Company::all();
            $dp = Departement::all();
            return $this->sendResponse([
                'user' => $dt,
                'company_select' => $cp,
                'dept_select' => $dp
            ], 'Data User berhasil dimuat');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function reset(int $id)
    {
        try {
            // Menyimpan data User ke database
            $dt = User::find($id);
            $dt->device_id = null;
            $dt->save();
            return $this->sendResponse($dt, 'Data User berhasil direset');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function reset_password(int $id)
    {
        try {
            // Cari user berdasarkan ID
            $user = User::find($id);
            if (!$user) {
                return $this->sendError('User tidak ditemukan.', [], 404);
            }
            // Reset password ke NIP (dihash)
            $user->password = Hash::make($user->nip);
            $user->save();

            return $this->sendResponse($user, 'Password user berhasil direset ke NIP.');
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat mereset password.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $this->validateUser($request);

        try {
            $user = User::findOrFail($id);

            // Update basic fields
            $user->update([
                'company_id' => $validated['company_id'],
                'name' => $validated['name'],
                'nip' => $validated['nip'],
                'email' => $validated['email'],
                'status' => $validated['status'],
            ]);

            // Update details (pastikan relasinya ada, misal hasOne)
            $user->details()->updateOrCreate([], $validated['details']);
            $user->address()->updateOrCreate([], $validated['address']);
            $user->salaries()->updateOrCreate([], $validated['salaries']);
            $user->employee()->updateOrCreate([], $validated['employee']);

            // Update avatar jika ada
            if ($request->hasFile('avatar')) {
                UploadFile::removeFromSpaces($user->avatar);
                $upload = UploadFile::uploadToSpaces($request->file('avatar'), 'avatars', Carbon::now()->format('YmdHis'));
                if (is_array($upload) && isset($upload['url']) && isset($upload['path'])) {
                    $user->update(['avatar' => $upload['path']]);
                }
            }

            return $this->sendResponse($user->fresh(), 'Data User berhasil diperbaharui');
        } catch (\Exception $e) {
            return $this->sendError('Terjadi kesalahan saat memperbaharui data.', ['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $idData = explode(',', $id);
        try {
            $dt = TimeWork::whereIn('id', $idData)->delete();
            return $this->sendResponse($dt, 'Data User berhasil dihapus');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */

    public function downloadpdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        // Validasi input
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255', // atau 'exists:companies,id' jika ini ID
            'departemen' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50', // atau 'in:admin,user,manager' sesuai kebutuhan
            'createdAt' => 'nullable|date',
            'updatedAt' => 'nullable|date',
            'startRange' => 'nullable|date',
            'endRange' => 'nullable|date|after_or_equal:startRange',
        ]);

        // Build query berdasarkan input yang tervalidasi
        $query = User::with([
            'company',
            'employee.departement',
            'employee.jobPosition',
            'employee.jobLevel',
        ]);
        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }
        if (!empty($validated['company'])) {
            $query->where('company_id', $validated['company']);
        }
        if (!empty($validated['departemen'])) {
            $query->whereHas('employee',function($emp)use($validated){
                $emp->where('departement_id', $validated['departemen']);
            });
        }
        if (!empty($validated['position'])) {
            $query->whereHas('employee',function($emp)use($validated){
                $emp->where('job_position_id', $validated['position']);
            });
        }
        if (!empty($validated['level'])) {
            $query->whereHas('employee',function($emp)use($validated){
                $emp->where('job_level_id', $validated['level']);
            });
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
            return response()->json(['message' => 'Tidak ada data User yang ditemukan.'], 404);
        }
        // // Generate PDF
        $pdf = Pdf::loadView('pdf.user', ['user' => $data]);
        $filename = 'user-' . now()->format('YmdHis') . '.pdf';

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
        $query = TimeWork::query();

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
            return response()->json(['message' => 'Tidak ada data User yang ditemukan.'], 404);
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
        $fileName = 'data_TimeWork_' . now()->format('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    private function validateUser(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['required', 'in:active,inactive,resign'],
            'details.phone' => ['required', 'string', 'max:20'],
            'details.placebirth' => ['required', 'string', 'max:100'],
            'details.datebirth' => ['required', 'date'],
            'details.gender' => ['required', 'in:m,f'],
            'details.blood' => ['nullable', 'in:a,b,ab,o'],
            'details.marital_status' => ['required', 'in:single,married,widow,widower'],
            'details.religion' => ['required', 'string', 'max:50'],
            'address.identity_type' => ['required', 'in:ktp,sim,passport'],
            'address.identity_numbers' => ['required', 'string', 'max:100'],
            'address.province' => ['required', 'string', 'max:100'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.citizen_address' => ['required', 'string', 'max:255'],
            'address.residential_address' => ['required', 'string', 'max:255'],
            'salaries.basic_salary' => ['required', 'numeric', 'min:0'],
            'salaries.payment_type' => ['required', 'in:Monthly,Weekly,Daily'],
            'employee.departement_id' => ['required', 'integer', 'exists:departements,id'],
            'employee.job_position_id' => ['required', 'integer', 'exists:job_positions,id'],
            'employee.job_level_id' => ['required', 'integer', 'exists:job_levels,id'],
            'employee.approval_line_id' => ['required', 'integer', 'exists:users,id'],
            'employee.approval_manager_id' => ['required', 'integer', 'exists:users,id'],
            'employee.join_date' => ['required', 'date'],
            'employee.sign_date' => ['required', 'date'],
            'employee.bank_name' => ['nullable', 'string', 'max:100'],
            'employee.bank_number' => ['nullable', 'string', 'max:50'],
            'employee.bank_holder' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'file', 'image', 'max:10048'],
        ]);
    }
}
