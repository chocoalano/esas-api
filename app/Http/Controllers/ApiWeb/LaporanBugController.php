<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\Tools\BugReport;
use App\Support\UploadFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanBugController extends Controller
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
            'search.company' => 'nullable|integer',
            'search.title' => 'nullable|string',
            'search.status' => 'nullable|in:0,1,true,false',
            'search.message' => 'nullable|string',
            'search.platform' => 'nullable|in:web,android,ios',
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.startRange' => 'nullable|date_format:Y-m-d',
            'search.endRange' => 'nullable|date_format:Y-m-d',
        ]);
        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        $query = BugReport::with(['company', 'user']);
        // Filtering
        if (!empty($search['title'])) {
            $query->where('title', 'like', '%' . $search['title'] . '%');
        }
        if (!empty($search['message'])) {
            $query->where('message', 'like', '%' . $search['message'] . '%');
        }
        if (!empty($search['platform'])) {
            $query->where('platform', $search['platform']);
        }
        if (!empty($search['company'])) {
            $query->where('company_id', $search['company']);
        }
        // Harus dicek dengan array_key_exists agar false tetap dihitung
        if (array_key_exists('status', $search)) {
            $query->where('status', $search['status'] === 'true' ? 1 : 0);
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
        $BugReports = $query->paginate($limit, ['*'], 'page', $page);
        return $this->sendResponse($BugReports, 'Data laporan bug berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'status' => 'required|in:true,false',
            'message' => 'required|string',
            'platform' => 'required|string|in:web,android,ios',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB = 10240 KB
        ]);
        if ($request->hasFile('image')) {
            $upload = UploadFile::uploadToSpaces($request->file('image'), 'laporan-bug', Carbon::now()->format('YmdHis'));
            if (is_array($upload) && isset($upload['url']) && isset($upload['path'])) {
                $validated['image'] = $upload['path'];
            }
        }
        $validated['company_id'] = Auth::user()->company_id;
        $validated['user_id'] = Auth::user()->id;
        $validated['status'] = $validated['status'] === 'true' ? 1 : 0;
        try {
            $dt = BugReport::create($validated);

            return $this->sendResponse($dt, 'Data laporan bug berhasil dibuat.');
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
            // Menyimpan data laporan bug ke database
            $dt = BugReport::with([
                'company',
                'user'
            ])->find($id);
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
        $q = BugReport::findOrFail($id);
        // Validasi tanpa image terlebih dahulu
        $rules = [
            'title' => 'required|string',
            'status' => 'required|in:true,false',
            'message' => 'required|string',
            'platform' => 'required|string|in:web,android,ios',
        ];
        // Tambahkan validasi image hanya jika ada file
        if ($request->hasFile('image')) {
            $rules['image'] = 'file|image|mimes:jpeg,png,jpg,webp|max:10240'; // 10MB
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $validated = $validator->validated();
        // Upload jika ada file image baru
        if ($request->hasFile('image')) {
            UploadFile::removeFromSpaces($q->image);
            $upload = UploadFile::uploadToSpaces(
                $request->file('image'),
                'laporan-bug',
                now()->format('YmdHis')
            );
            if (is_array($upload) && isset($upload['path'])) {
                $validated['image'] = $upload['path'];
            }
        } else {
            $validated['image'] = $q->image; // Tetap gunakan image lama
        }
        $validated['company_id'] = Auth::user()->company_id;
        $validated['user_id'] = Auth::user()->id;
        $validated['status'] = $validated['status'] === 'true' ? 1 : 0;
        try {
            $q->update($validated);
            return $this->sendResponse($q, 'Data laporan bug berhasil diperbaharui');
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
            $dt = BugReport::whereIn('id', $idData);
            $file = $dt->get();
            foreach ($file as $k) {
                UploadFile::removeFromSpaces($k['image']);
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
        $query = BugReport::query();
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
        // Ambil data hasil filter
        $companies = $query->get();
        // Jika tidak ada data, bisa kasih fallback (opsional)
        if ($companies->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data laporan bug yang ditemukan.'], 404);
        }
        // // Generate PDF
        $pdf = Pdf::loadView('pdf.company', ['company' => $companies]);
        $filename = 'company-' . now()->format('YmdHis') . '.pdf';

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
        $query = BugReport::query();

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
