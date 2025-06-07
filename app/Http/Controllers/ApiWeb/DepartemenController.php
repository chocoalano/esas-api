<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\CoreApp\Departement;
use App\Models\CoreApp\Company;
use App\Support\Logger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartemenController extends Controller
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
        $query = Departement::query();
        $query->with('company');
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
        Logger::log('list paginate', $data->first() ?? new Departement(), $data->toArray());
        return $this->sendResponse($data, 'Data departemen berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|numeric|exists:companies,id',
            'name' => 'required|string|max:255',
        ]);

        try {
            $data = Departement::create([
                'company_id' => $validated['company_id'],
                'name' => $validated['name'],
            ]);
            Logger::log('create', new Departement(), $data->toArray());
            return $this->sendResponse($data, 'Data departemen berhasil dibuat.');
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
            // Menyimpan data departemen ke database
            $dt = Departement::with([
                'company',
                'jobPositions',
                'jobLevels',
                'employees.user',
            ])
                ->find($id);
            $cy = Company::all();
            Logger::log('show', new Departement(), $dt->toArray());
            return $this->sendResponse([
                'departemen' => $dt,
                'select_company' => $cy
            ], 'Data departemen berhasil dimuat');
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
            'name' => 'required|string|max:255',
        ]);
        try {
            // Menyimpan data departemen ke database
            $dt = Departement::find($id);
            $payload = [
                'before' => $dt->toArray(),
                'after' => $validated,
            ];
            $dt->update([
                'company_id' => $validated['company_id'],
                'name' => $validated['name'],
            ]);
            Logger::log('update', new Departement(), $payload);
            return $this->sendResponse($dt, 'Data departemen berhasil diperbaharui');
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
            $dt = Departement::whereIn('id', $idData);
            $delete = $dt->get();
            Logger::log('delete', new Departement(), $delete->toArray());
            $dt->delete();
            return $this->sendResponse($dt, 'Data departemen berhasil dihapus');
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
            'company_id' => 'nullable|numeric|exists:companies,id',
            'name' => 'nullable|string|max:255',
        ]);

        // Build query berdasarkan input yang tervalidasi
        $query = Departement::with('company');

        if (!empty($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }

        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }

        // Ambil data hasil filter
        $datas = $query->get();

        // Jika tidak ada data, kembalikan respon 404
        if ($datas->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data departemen yang ditemukan.'], 404);
        }

        // Generate PDF
        $pdf = Pdf::loadView('pdf.departement', ['departement' => $datas]);
        $filename = 'departement-' . now()->format('YmdHis') . '.pdf';
        Logger::log('pdf download', new Departement(), $datas->toArray());
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function downloadExcel(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'company' => ['nullable', 'string'],
            'radius' => ['nullable', 'numeric', 'min:0'],
            'createdAt' => ['nullable', 'date_format:Y-m-d'],
            'updatedAt' => ['nullable', 'date_format:Y-m-d'],
            'startRange' => ['nullable', 'date_format:Y-m-d'],
            'endRange' => ['nullable', 'date_format:Y-m-d'],
        ]);

        // Validasi tambahan: startRange <= endRange
        if (!empty($validated['startRange']) && !empty($validated['endRange']) && $validated['startRange'] > $validated['endRange']) {
            return response()->json(['message' => 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.'], 422);
        }

        // Query builder
        $query = Departement::query();

        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }

        if (!empty($validated['company'])) {
            $query->whereHas('company', function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['company'] . '%');
            });
        }

        if (!empty($validated['createdAt'])) {
            $query->whereDate('created_at', $validated['createdAt']);
        }

        if (!empty($validated['updatedAt'])) {
            $query->whereDate('updated_at', $validated['updatedAt']);
        }

        if (!empty($validated['startRange']) && !empty($validated['endRange'])) {
            $query->whereBetween('created_at', [$validated['startRange'], $validated['endRange']]);
        }

        $departments = $query->get();

        if ($departments->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data departemen yang ditemukan.'], 404);
        }

        // Generate spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $sheet->fromArray(['No', 'Nama', 'Radius', 'Dibuat', 'Diperbarui'], null, 'A1');

        // Data
        $row = 2;
        foreach ($departments as $index => $dept) {
            $sheet->fromArray([
                $index + 1,
                $dept->name,
                $dept->radius,
                optional($dept->created_at)->format('Y-m-d H:i:s'),
                optional($dept->updated_at)->format('Y-m-d H:i:s'),
            ], null, 'A' . $row);
            $row++;
        }

        $fileName = 'data_departemen_' . now()->format('Ymd_His') . '.xlsx';

        // Stream response
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        Logger::log('xlsx download', new Departement(), $departments->toArray());
        return $response;
    }
}
