<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tools\BugReportRequest;
use App\Http\Resources\Tools\BugReportListPaginationResource;
use App\Repositories\Interfaces\Tools\BugReportInterface;
use App\Support\UploadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BugReportController extends Controller
{
    protected $proses;

    public function __construct(BugReportInterface $proses)
    {
        $this->proses = $proses;
        $this->middleware('auth');
    }
    public function index(Request $request)
    {
        try {
            // Ambil parameter request dengan nilai default
            $page = $request->input('page', 1); // Default ke halaman 1 jika tidak ada
            $limit = $request->input('limit', 10); // Default ke 10 jika tidak ada
            $search = $request->input('search', ''); // Default ke string kosong jika tidak ada

            // Pastikan nilai search adalah string
            $search = is_string($search) ? $search : '';

            // Panggil metode paginate
            $data = $this->proses->paginate($page, $limit, $search);
            $response = new BugReportListPaginationResource($data);

            // Kembalikan response berhasil
            return $this->sendResponse($response, 'Bug reports show list successfully.');
        } catch (\Exception $e) {
            // Kembalikan response error
            return $this->sendError('Bug report listing failed.', ['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $valid = Validator::make($request->all(), BugReportRequest::rules());

        if ($valid->fails()) {
            return $this->sendError('Validation Error.', $valid->errors(), 422);
        }
        $this->proses->create($valid->getData());
        try {
            $valid = Validator::make($request->all(), BugReportRequest::rules());

            if ($valid->fails()) {
                return $this->sendError('Validation Error.', $valid->errors(), 422);
            }
            $response = $this->proses->create($valid->getData());
            return $this->sendResponse($response, 'Bug reports show list successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Bug report created failed.', ['error' => $e->getMessage()], 500);
        }
    }
}
