<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\CoreApp\Company;
use App\Models\CoreApp\Departement;
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
            'sortBy.*.key' => 'required_with:sortBy|string|in:name,nip,email,status,created_at,updated_at',
            'sortBy.*.order' => 'required_with:sortBy|string|in:asc,desc',

            'search' => 'nullable|array',
            'search.company' => ['nullable', 'exists:companies,id'],
            'search.departemen' => ['nullable', 'exists:departements,id'],
            'search.position' => ['nullable', 'exists:job_positions,id'],
            'search.level' => ['nullable', 'exists:job_levels,id'],
            'search.nip' => ['nullable', 'string', 'max:50'],
            'search.name' => ['nullable', 'string', 'max:100'],
            'search.email' => ['nullable', 'email'],
            'search.status' => ['nullable', 'in:active,inactive,resign'],
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.startRange' => 'nullable|date_format:Y-m-d',
            'search.endRange' => 'nullable|date_format:Y-m-d|after_or_equal:search.startRange',
        ]);

        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];

        $query = User::with([
            'company',
            'details',
            'employee',
            'employee.departement',
            'employee.jobPosition',
            'employee.jobLevel',
        ]);

        // Filter langsung pada User
        $query->when($search['company'] ?? null, fn($q, $value) => $q->where('company_id', $value))
            ->when($search['nip'] ?? null, fn($q, $value) => $q->where('nip', 'like', "%$value%"))
            ->when($search['name'] ?? null, fn($q, $value) => $q->where('name', 'like', "%$value%"))
            ->when($search['email'] ?? null, fn($q, $value) => $q->where('email', 'like', "%$value%"))
            ->when($search['status'] ?? null, fn($q, $value) => $q->where('status', $value))
            ->when($search['createdAt'] ?? null, fn($q, $value) => $q->whereDate('created_at', $value))
            ->when($search['updatedAt'] ?? null, fn($q, $value) => $q->whereDate('updated_at', $value))
            ->when(
                isset($search['startRange'], $search['endRange']),
                fn($q) => $q->whereBetween('updated_at', [$search['startRange'], $search['endRange']])
            );

        // Filter berdasarkan relasi employee
        if (!empty($search['departemen']) || !empty($search['position']) || !empty($search['level'])) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->when($search['departemen'] ?? null, fn($q, $val) => $q->where('departement_id', $val))
                    ->when($search['position'] ?? null, fn($q, $val) => $q->where('job_position_id', $val))
                    ->when($search['level'] ?? null, fn($q, $val) => $q->where('job_level_id', $val));
            });
        }

        // Sorting
        if (!empty($sortBy)) {
            foreach ($sortBy as $sort) {
                $query->orderBy($sort['key'], $sort['order']);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginate
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
            $user = new User();
            $user->company_id = $validated['company_id'];
            $user->name = $validated['name'];
            $user->nip = $validated['nip'];
            $user->email = $validated['email'];
            $user->status = $validated['status'];
            $user->password = Hash::make($validated['password']);

            // Upload avatar jika ada
            if ($request->hasFile('avatar')) {
                $upload = UploadFile::uploadToSpaces(
                    $request->file('avatar'),
                    'avatars',
                    Carbon::now()->format('YmdHis')
                );

                if (is_array($upload) && isset($upload['path'])) {
                    $user->avatar = $upload['path'];
                }
            }

            $user->save();

            // Update atau buat relasi terkait jika tersedia di data validasi
            if (isset($validated['details'])) {
                $user->details()->updateOrCreate([], $validated['details']);
            }

            if (isset($validated['address'])) {
                $user->address()->updateOrCreate([], $validated['address']);
            }

            if (isset($validated['salaries'])) {
                $user->salaries()->updateOrCreate([], $validated['salaries']);
            }

            if (isset($validated['employee'])) {
                $user->employee()->updateOrCreate([], $validated['employee']);
            }

            return $this->sendResponse($user, 'Data User berhasil dibuat.');

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
        try {
            $dt = User::find($id)->delete();
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
            $query->whereHas('employee', function ($emp) use ($validated) {
                $emp->where('departement_id', $validated['departemen']);
            });
        }
        if (!empty($validated['position'])) {
            $query->whereHas('employee', function ($emp) use ($validated) {
                $emp->where('job_position_id', $validated['position']);
            });
        }
        if (!empty($validated['level'])) {
            $query->whereHas('employee', function ($emp) use ($validated) {
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
        ini_set('memory_limit', '512M');
        // Validasi input
        $validated = $request->validate([
            'company' => ['required', 'exists:companies,id'],
            'departemen' => ['nullable', 'exists:departments,id'],
            'position' => ['nullable', 'exists:job_positions,id'],
            'level' => ['nullable', 'exists:job_levels,id'],
            'nip' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email'],
            'status' => ['nullable', 'in:active,inactive,resign'],
            'start' => ['nullable', 'date', 'before_or_equal:end'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        // Build query berdasarkan input
        $query = User::with('company')->where('company_id', $validated['company']);

        if (!empty($validated['departemen'])) {
            $query->whereHas('employee', fn($q) => $q->where('departement_id', $validated['departemen']));
        }

        if (!empty($validated['position'])) {
            $query->whereHas('employee', fn($q) => $q->where('position_id', $validated['position']));
        }

        if (!empty($validated['level'])) {
            $query->whereHas('employee', fn($q) => $q->where('level_id', $validated['level']));
        }

        if (!empty($validated['nip'])) {
            $query->where('nip', 'like', '%' . $validated['nip'] . '%');
        }

        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }

        if (!empty($validated['email'])) {
            $query->where('email', 'like', '%' . $validated['email'] . '%');
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['start']) && !empty($validated['end'])) {
            $query->whereBetween('created_at', [
                $validated['start'],
                $validated['end']
            ]);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data User yang ditemukan.'], 404);
        }

        // Buat Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'NIP');
        $sheet->setCellValue('C1', 'Nama');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'Status');
        $sheet->setCellValue('F1', 'Perusahaan');
        $sheet->setCellValue('G1', 'Tanggal Dibuat');
        $sheet->setCellValue('H1', 'Tanggal Diperbarui');

        // Isi data
        $row = 2;
        foreach ($users as $i => $user) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $user->nip);
            $sheet->setCellValue('C' . $row, $user->name);
            $sheet->setCellValue('D' . $row, $user->email);
            $sheet->setCellValue('E' . $row, $user::STATUS[$user->status] ?? $user->status);
            $sheet->setCellValue('F' . $row, $user->company->name ?? '-');
            $sheet->setCellValue('G' . $row, $user->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('H' . $row, $user->updated_at->format('Y-m-d H:i:s'));
            $row++;
        }

        // Buat stream response
        $fileName = 'data_user_' . now()->format('Ymd_His') . '.xlsx';

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
            'password' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,resign'],
            'details.phone' => ['required', 'string', 'max:20'],
            'details.placebirth' => ['required', 'string', 'max:100'],
            'details.datebirth' => ['required', 'date'],
            'details.gender' => ['required', 'in:m,w'],
            'details.blood' => ['required', 'in:a,b,ab,o'],
            'details.marital_status' => ['required', 'in:single,married,widow,widower'],
            'details.religion' => ['required', 'in:islam,protestan,khatolik,hindu,buddha,khonghucu'],
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
            'employee.bank_name' => ['required', 'string', 'max:100'],
            'employee.bank_number' => ['required', 'string', 'max:30'],
            'employee.bank_holder' => ['required', 'string', 'max:100'],
            'avatar' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,heic,heif',
                'max:10048', // 10 MB
            ],
        ]);
    }
}
