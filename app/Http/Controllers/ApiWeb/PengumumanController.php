<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\AdministrationApp\Announcement;
use App\Models\CoreApp\Company;
use App\Support\Logger;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengumumanController extends Controller
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
            'search.content' => 'nullable|string',
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.startRange' => 'nullable|date_format:Y-m-d',
            'search.endRange' => 'nullable|date_format:Y-m-d',
        ]);
        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        $query = Announcement::with(['company', 'user']);
        // Filtering
        if (!empty($search['title'])) {
            $query->where('title', 'like', '%' . $search['title'] . '%');
        }
        if (!empty($search['content'])) {
            $query->where('content', 'like', '%' . $search['content'] . '%');
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
        $data = $query->paginate($limit, ['*'], 'page', $page);
        Logger::log('list paginate', new Announcement(), $data->toArray());
        return $this->sendResponse($data, 'Data pengumuman berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|numeric|exists:companies,id',
            'title' => 'required|string|max:255',
            'status' => 'required|boolean',
            'content' => 'required|string',
        ]);
        $validated['user_id'] = Auth::user()->id;
        try {
            $dt = Announcement::create($validated);
            Logger::log('create', $dt ?? new Announcement(), $dt->toArray());
            return $this->sendResponse($dt, 'Data pengumuman berhasil dibuat.');
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
            // Menyimpan data pengumuman ke database
            $dt = Announcement::with([
                'company',
                'user'
            ])->find($id);
            $cy = Company::all();
            Logger::log('show', $dt ?? new Announcement(), $dt->toArray());
            return $this->sendResponse([
                'pengumuman' => $dt,
                'select_company' => $cy
            ], 'Data pengumuman berhasil dimuat');
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
            'company_id' => 'required|numeric|exists:companies,id',
            'title' => 'required|string|max:255',
            'status' => 'required|boolean',
            'content' => 'required|string',
        ]);
        $validated['user_id'] = Auth::user()->id;
        try {
            // Menyimpan data pengumuman ke database
            $dt = Announcement::find($id);
            $payload = [
                'before' => $dt->toArray(),
                'after' => $validated,
            ];
            Logger::log('update', $dt ?? new Announcement(), $payload);
            $dt->update($validated);

            return $this->sendResponse($dt, 'Data pengumuman berhasil diperbaharui');
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
            $dt = Announcement::whereIn('id', $idData);
            $delete = $dt->get;
            Logger::log('delete', $dt ?? new Announcement(), $delete->toArray());
            $dt->delete();
            return $this->sendResponse($dt, 'Data pengumuman berhasil dihapus');
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
        $query = Announcement::query();
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
        $data = $query->get();
        // Jika tidak ada data, bisa kasih fallback (opsional)
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data pengumuman yang ditemukan.'], 404);
        }
        // // Generate PDF
        $pdf = Pdf::loadView('pdf.company', ['company' => $data]);
        $filename = 'company-' . now()->format('YmdHis') . '.pdf';
        Logger::log('pdf download', new Announcement(), $data->toArray());
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
        $query = Announcement::query();

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

        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data pengumuman yang ditemukan.'], 404);
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
        foreach ($data as $i => $dt) {
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
        Logger::log('xlsx download', new Announcement(), $data->toArray());
        return $response;
    }
}
