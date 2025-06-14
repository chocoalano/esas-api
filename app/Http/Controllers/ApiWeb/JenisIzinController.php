<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\AdministrationApp\PermitType;
use App\Support\Logger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JenisIzinController extends Controller
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
            'search.radius' => 'nullable|numeric|min:0',
            'search.createdAt' => 'nullable|date_format:Y-m-d',
            'search.updatedAt' => 'nullable|date_format:Y-m-d',
            'search.startRange' => 'nullable|date_format:Y-m-d',
            'search.endRange' => 'nullable|date_format:Y-m-d',
        ]);

        $page = $validated['page'];
        $limit = $validated['limit'];
        $search = $validated['search'] ?? [];
        $sortBy = $validated['sortBy'] ?? [];
        $query = PermitType::query();
        // Filtering
        if (!empty($search)) {
            if (!empty($search['name'])) {
                $query->where('type', 'like', '%' . $search['name'] . '%');
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
        $data = $query->paginate($limit, ['*'], 'page', $page);
        if (!auth()->user()->hasRole('super_admin')) {
            Logger::log('list paginate', new PermitType(), $data->toArray());
        }
        return $this->sendResponse($data, 'Data permittype berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'is_payed' => ['required', 'boolean'],
            'approve_line' => ['required', 'boolean'],
            'approve_manager' => ['required', 'boolean'],
            'approve_hr' => ['required', 'boolean'],
            'with_file' => ['required', 'boolean'],
            'show_mobile' => ['required', 'boolean'],
        ]);

        try {
            $data = PermitType::create($validated);
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('create', $data ?? new PermitType(), $data->toArray());
            }
            return $this->sendResponse($data, 'Data permittype berhasil dibuat.');
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
            // Menyimpan data permittype ke database
            $dt = PermitType::find($id);
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('show', $dt ?? new PermitType(), $dt->toArray());
            }
            return $this->sendResponse($dt, 'Data permittype berhasil dimuat');
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
            'type' => ['required', 'string', 'max:255'],
            'is_payed' => ['required', 'boolean'],
            'approve_line' => ['required', 'boolean'],
            'approve_manager' => ['required', 'boolean'],
            'approve_hr' => ['required', 'boolean'],
            'with_file' => ['required', 'boolean'],
            'show_mobile' => ['required', 'boolean'],
        ]);
        try {
            // Menyimpan data permittype ke database
            $dt = PermitType::find($id);
            $payload = [
                'before' => $dt->toArray(),
                'after' => $validated
            ];
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('update', $dt ?? new PermitType(), $payload);
            }
            $dt->update($validated);

            return $this->sendResponse($dt, 'Data permittype berhasil diperbaharui');
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
            $dt = PermitType::whereIn('id', $idData);
            $delete = $dt->get();
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('delete', $dt ?? new PermitType(), $delete->toArray());
            }
            $dt->delete();
            return $this->sendResponse($dt, 'Data permittype berhasil dihapus');
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
        $query = PermitType::query();
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
            return response()->json(['message' => 'Tidak ada data permittype yang ditemukan.'], 404);
        }
        // // Generate PDF
        $pdf = Pdf::loadView('pdf.jenis_izin', ['jenis_izin' => $data]);
        $filename = 'jenis_izin-' . now()->format('YmdHis') . '.pdf';
        if (!auth()->user()->hasRole('super_admin')) {
            Logger::log('pdf download', new PermitType(), $data->toArray());
        }
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
        $query = PermitType::query();

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
            return response()->json(['message' => 'Tidak ada data permittype yang ditemukan.'], 404);
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
        $fileName = 'data_permittype_' . now()->format('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        if (!auth()->user()->hasRole('super_admin')) {
            Logger::log('xlsx download', new PermitType(), $data->toArray());
        }
        return $response;
    }
}
