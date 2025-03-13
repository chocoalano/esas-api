<?php

namespace App\Http\Controllers;

use App\FcmNotification\PermitNotification;
use Illuminate\Http\JsonResponse;

class FirebaseImplementController extends Controller
{
    protected $notif;

    public function __construct(PermitNotification $notif)
    {
        $this->notif = $notif;
    }

    public function index(): JsonResponse
    {
        $user_target = [137];
        try {
            $this->notif->broadcast_approvals($user_target, 'Alan gentina', 'Cuti Tahunan');

            return response()->json([
                'status' => 'success',
                'message' => 'Notification broadcasted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to broadcast notification',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
