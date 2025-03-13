<?php

namespace App\Http\Controllers;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\FcmModel;
use Illuminate\Http\JsonResponse;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Messaging;

class FirebaseController extends Controller
{
    protected Messaging $messaging;

    public function __construct()
    {
        try {
            // Ambil path credentials dari config/firebase.php
            $credentialsPath = config('firebase.projects.app.credentials');
            // dd(file_exists(storage_path($credentialsPath)) ? storage_path($credentialsPath) : 'notfound');
            if (!$credentialsPath) {
                throw new \Exception("Firebase credentials path is missing in config.");
            }

            // Inisialisasi Firebase Messaging
            $factory = (new Factory)->withServiceAccount(storage_path($credentialsPath));
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            // Log error jika Firebase gagal diinisialisasi
            \Log::error("Firebase initialization failed: " . $e->getMessage());
            abort(500, "Failed to initialize Firebase.");
        }
    }

    public function index(): JsonResponse
    {
        try {
            // Ambil token FCM dari database berdasarkan user_id
            $fcmToken = FcmModel::where('user_id', 137)->value('device_token');

            if (!$fcmToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'FCM token not found for this user.'
                ], 404);
            }

            // Validasi apakah token memiliki panjang yang sesuai (bisa dicek dengan panjang minimal)
            if (strlen($fcmToken) < 20) { // Panjang token FCM biasanya lebih panjang
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid FCM token detected.'
                ], 400);
            }

            // Buat notifikasi
            $notification = Notification::create('Pemberitahuan', 'Ini adalah notif dari Laravel');

            // Buat pesan FCM
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData([
                    'info' => "ini info testing",
                    'user' => json_encode([137, 1, 6, 3])
                ]);

            // Kirim pesan
            $this->messaging->send($message);

            return response()->json([
                'status' => 'success',
                'message' => 'Notification sent successfully.'
            ], 200);
        } catch (MessagingException $e) {
            \Log::error("Firebase MessagingException: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        } catch (FirebaseException $e) {
            \Log::error("FirebaseException: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Firebase error: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            \Log::error("Unexpected error: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
