<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdministrationApp\PermitRequest;
use App\Http\Resources\Permit\PermitListPaginationResource;
use App\Repositories\Interfaces\AdministrationApp\PermitInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PermitController extends Controller
{
    protected $proses;

    public function __construct(PermitInterface $proses)
    {
        $this->proses = $proses;
        $this->middleware('auth');
        $this->middleware('permission:view_permit|view_any_permit', ['only' => ['index', 'show']]);
        $this->middleware('permission:create_permit', ['only' => ['store']]);
        $this->middleware('permission:update_permit', ['only' => ['update']]);
        $this->middleware('permission:delete_permit|delete_any_permit', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $input = $request->only('page', 'limit', 'search', 'type');
            $data = $this->proses->paginate($input['page'], $input['limit'], $input['search'], $input['type']);
            $response = new PermitListPaginationResource($data);
            return $this->sendResponse($response, 'Permit show list successfully.');
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
            $input = $request->all();
            if (isset($input['type']) && $input['type'] === 'mobile') {
                $input['user_id']=Auth::user()->id;
                $input['permit_numbers']=$this->proses->generate_unique_numbers($input['permit_type_id']);
            }
            $permitTypeId = $input['permit_type_id'];
            $validationRules = match ($permitTypeId) {
                15 => PermitRequest::koreksi_absen(),
                16 => PermitRequest::perubahan_shift(),
                default => PermitRequest::lainya(),
            };

            // Validasi data
            $valid = Validator::make($input, $validationRules);
            if ($valid->fails()) {
                return $this->sendError('Validation Error.', ['errors' => $valid->errors()], 422);
            }
            $input = $valid->getData();
            $created = $this->proses->create($input);

            return $this->sendResponse($created, 'Permit created successfully.');
        } catch (\Throwable $e) {
            // Tangkap semua jenis error
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
            return $this->sendResponse($show, 'Permit show successfully.');
        } catch (\Throwable $e) {
            // Tangkap semua jenis error
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $authId = Auth::user()->id;
            $approve = $request->input('approve');
            $notes = $request->input('notes');
            $approve = $this->proses->approved($id, $authId, $approve, $notes);
            return $this->sendResponse($approve, 'Permit update successfully.');
        } catch (\Throwable $e) {
            // Tangkap semua jenis error
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
