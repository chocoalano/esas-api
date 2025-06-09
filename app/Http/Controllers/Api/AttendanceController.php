<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdministrationApp\AttendanceRequest;
use App\Http\Resources\Attendance\AttendanceDetailResource;
use App\Http\Resources\Attendance\AttendanceLIstPaginateResource;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\AdministrationApp\LogUserAttendance;
use App\Repositories\Interfaces\AdministrationApp\AttendanceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    protected $proses;

    public function __construct(AttendanceInterface $proses)
    {
        $this->proses = $proses;
        $this->middleware('auth');
        $this->middleware('permission:view_user::attendance|view_any_user::attendance', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_user::attendance', ['only' => ['store']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function auth_all(Request $request)
    {
        try {
            $input = $request->only('search');
            $data = $this->proses->auth_all($input['search']);
            $response = AttendanceResource::collection($data);
            return $this->sendResponse($response, 'Attendance show list successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Process errors.', ['error' => $e->getMessage()]);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $input = $request->only('page', 'limit', 'search');
            $data = $this->proses->paginate($input['page'], $input['limit'], $input['search']);
            $response = new AttendanceLIstPaginateResource($data);
            return $this->sendResponse($response, 'Attendance show list successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Process errors.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $valid = Validator::make($request->all(), AttendanceRequest::presence());
            if ($valid->fails()) {
                return $this->sendError('Validation Error.', ['errors' => $valid->errors()], 422);
            }
            $input = $valid->getData();
            $created = $input['type'] === 'in' ?
                $this->proses->presence_in($input) :
                $this->proses->presence_out($input);
            LogUserAttendance::create([
                'user_id' => Auth::user()->id,
                'type' => $input['type'],
            ]);
            return $created ?
                $this->sendResponse($created, 'Attendance created successfully.') :
                $this->sendError('Process error.', ['error' => $created], 500);
        } catch (\Throwable $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $show = $this->proses->find($id);
            $response = new AttendanceDetailResource($show);
            return $this->sendResponse($response, 'Attendance show successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        // Ambil data dari service
        $process = app(AttendanceInterface::class);
        $data = $process->report($request->start, $request->end);

        if (empty($data)) {
            return response()->json(['error' => 'No data available for the selected period'], 400);
        }

        // Buat Spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Header
        $headers = array_keys((array) $data[0]); // Ambil key dari array pertama
        $columnIndex = 'A';

        foreach ($headers as $header) {
            $sheet->setCellValue($columnIndex . '1', strtoupper($header));
            $columnIndex++;
        }

        // Isi Data
        $rowNumber = 2;
        foreach ($data as $row) {
            $columnIndex = 'A';
            foreach ((array) $row as $value) {
                $sheet->setCellValue($columnIndex . $rowNumber, $value);
                $columnIndex++;
            }
            $rowNumber++;
        }

        // Simpan sebagai file Excel dan kirim ke browser
        $writer = new Xlsx($spreadsheet);
        $fileName = "attendance_report.xlsx";

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
