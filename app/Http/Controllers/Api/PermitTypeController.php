<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Permit\PermitTypeJsonResource;
use App\Repositories\Interfaces\AdministrationApp\PermitInterface;
use Illuminate\Support\Facades\Cache;

class PermitTypeController extends Controller
{
    protected $proses;

    public function __construct(PermitInterface $proses)
    {
        $this->proses = $proses;
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Gunakan cache untuk menyimpan hasil proses
            $response = Cache::remember('permit_type_list', now()
                ->addMinutes(10), function () {
                    $data = $this->proses->type(); // Ambil data hanya jika tidak ada di cache
                    return PermitTypeJsonResource::collection($data);
                });

            return $this->sendResponse($response, 'Permit type show list successfully.');
        } catch (\Exception $e) {
            // Tangani kesalahan
            return $this->sendError('Process errors.', ['error' => $e->getMessage()]);
        }
    }
}
