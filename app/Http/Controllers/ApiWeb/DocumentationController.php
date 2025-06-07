<?php

namespace App\Http\Controllers\ApiWeb;

use App\Http\Controllers\Controller;
use App\Models\Documentation;
use App\Support\Logger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentationController extends Controller
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
        $query = Documentation::query();
        // Filtering
        if (!empty($search)) {
            if (!empty($search['title'])) {
                $query->where('title', 'like', '%' . $search['title'] . '%');
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
        Logger::log('list paginate', new Documentation(), $data->toArray());
        return $this->sendResponse($data, 'Data documentation berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'text_docs' => ['required', 'string'],
            'status' => ['required', Rule::in(['true', 'false', true, false, 1, 0, '1', '0']),],
        ]);

        try {
            $data = Documentation::create($validated);
            Logger::log('create', new Documentation(), $data->toArray());
            return $this->sendResponse($data, 'Data documentation berhasil dibuat.');
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
            // Menyimpan data documentation ke database
            $dt = Documentation::find($id);
            Logger::log('show', new Documentation(), $dt->toArray());
            return $this->sendResponse($dt, 'Data documentation berhasil dimuat');
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
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'text_docs' => ['required', 'string'],
            'status' => ['required', Rule::in(['true', 'false', true, false, 1, 0, '1', '0']),],
        ]);
        try {
            // Menyimpan data documentation ke database
            $dt = Documentation::find($id);
            $payload = [
                'before'=>$dt->toArray(),
                'after'=>$validated,
            ];
            Logger::log('create', New Documentation(), $payload);
            $dt->update($validated);

            return $this->sendResponse($dt, 'Data documentation berhasil diperbaharui');
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
            $dt = Documentation::whereIn('id', $idData);
            $delete = $dt->get();
            Logger::log('delete', New Documentation(), $delete->toArray());
            $delete->delete();
            return $this->sendResponse($dt, 'Data documentation berhasil dihapus');
        } catch (\Exception $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */

    public function downloadpdf($id)
    {
        // Ambil data hasil filter
        $data = Documentation::find($id);
        // Jika tidak ada data, bisa kasih fallback (opsional)
        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data documentation yang ditemukan.'], 404);
        }
        // Generate PDF
        $pdf = Pdf::loadView('pdf.position', ['position' => $data]);
        $filename = 'position-' . now()->format('YmdHis') . '.pdf';
        Logger::log('pdf download', new Documentation(), $data->toArray());
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
