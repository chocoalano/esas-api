<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\CoreApp\Company;
use App\Support\Logger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
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
        $query = Company::query();
        // Filtering
        if (!empty($search)) {
            if (!empty($search['name'])) {
                $query->where('name', 'like', '%' . $search['name'] . '%');
            }
            if (!empty($search['radius'])) {
                $query->where('radius', $search['radius']);
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
            Logger::log('list paginate', $data->first() ?? new Company(), $data->toArray());
        }
        return $this->sendResponse($data, 'Data perusahaan berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|numeric|min:1',
            'full_address' => 'required|string|max:500',
        ]);
        try {
            // Menyimpan data perusahaan ke database
            $data = Company::create([
                'name' => $validated['name'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'radius' => $validated['radius'],
                'full_address' => $validated['full_address'],
            ]);
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('create', $data ?? new Company(), $data->toArray());
            }
            return $this->sendResponse($data, 'Data perusahaan berhasil dibuat');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            // Menyimpan data perusahaan ke database
            $data = Company::find($id);
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('show', $data ?? new Company(), $data->toArray());
            }
            return $this->sendResponse($data, 'Data perusahaan berhasil dimuat');
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
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|numeric|min:1',
            'full_address' => 'required|string|max:500',
        ]);
        try {
            // Menyimpan data perusahaan ke database
            $data = Company::find($id);
            $payload = [
                'before' => $data->toArray(),
                'after' => $validated,
            ];
            $data->update([
                'name' => $validated['name'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'radius' => $validated['radius'],
                'full_address' => $validated['full_address'],
            ]);
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('update', $data ?? new Company(), $payload);
            }
            return $this->sendResponse($data, 'Data perusahaan berhasil diperbaharui');
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
            $data = Company::whereIn('id', $idData);
            $delete = $data->get();
            if (!auth()->user()->hasRole('super_admin')) {
                Logger::log('delete', $data ?? new Company(), $delete->toArray());
            }
            $data->delete();
            return $this->sendResponse($data, 'Data perusahaan berhasil dihapus');
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
        $query = Company::query();
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
            return response()->json(['message' => 'Tidak ada data perusahaan yang ditemukan.'], 404);
        }
        // // Generate PDF
        $pdf = Pdf::loadView('pdf.company', ['company' => $data]);
        $filename = 'company-' . now()->format('YmdHis') . '.pdf';
        if (!auth()->user()->hasRole('super_admin')) {
            Logger::log('pdf download', new Company(), $data->toArray());
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
        $query = Company::query();

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
            return response()->json(['message' => 'Tidak ada data perusahaan yang ditemukan.'], 404);
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
        foreach ($data as $i => $data) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $data->name);
            $sheet->setCellValue('C' . $row, $data->radius);
            $sheet->setCellValue('D' . $row, $data->created_at->format('Y-m-d H:i:s'));
            $sheet->setCellValue('E' . $row, $data->updated_at->format('Y-m-d H:i:s'));
            $row++;
        }

        // Buat stream response
        $fileName = 'data_perusahaan_' . now()->format('Ymd_His') . '.xlsx';

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        if (!auth()->user()->hasRole('super_admin')) {
            Logger::log('xlsx download', new Company(), $data->toArray());
        }
        return $response;
    }
}
