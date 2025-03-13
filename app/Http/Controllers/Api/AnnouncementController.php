<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Announcement\AnnouncementDetailResouces;
use App\Http\Resources\Announcement\AnnouncementLIstPaginateResource;
use App\Repositories\Interfaces\AdministrationApp\AnnouncementInterface;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    protected $proses;

    public function __construct(AnnouncementInterface $proses)
    {
        $this->proses = $proses;
        // $this->middleware('auth');
        // $this->middleware('permission:view_announcement|view_any_announcement', ['only' => ['index', 'show']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $input = $request->only('page', 'limit', 'search');
            $data = $this->proses->paginate($input['page'], $input['limit'], $input['search']);
            $response = new AnnouncementLIstPaginateResource($data);
            return $this->sendResponse($response, 'Announcement show list successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Process errors.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
            $show = $this->proses->find($id);
            $response = new AnnouncementDetailResouces($show);
            return $this->sendResponse($show, 'Announcement show successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Process error.', ['error' => $e->getMessage()], 500);
        }
    }
}
