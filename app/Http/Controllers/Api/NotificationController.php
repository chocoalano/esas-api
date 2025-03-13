<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoreApp\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validasi input
            $validated = $request->validate([
                'page' => 'sometimes|integer|min:1',
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);

            // Tetapkan nilai default untuk page dan limit jika tidak disediakan
            $page = $validated['page'] ?? 1;
            $limit = $validated['limit'] ?? 10;

            // Ambil data notifikasi dengan pagination
            $response = Notification::where('notifiable_id', auth()->id())
                ->whereNull('read_at')
                ->paginate($limit, ['*'], 'page', $page);
            // Kirim response sukses
            return $this->sendResponse($response, 'Notifications retrieved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Tanggapi jika validasi gagal
            return $this->sendError('Validation errors.', $e->errors(), 422);
        } catch (\Exception $e) {
            // Tangani error umum
            return $this->sendError('An error occurred while processing your request.', ['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            // Cari notifikasi berdasarkan ID
            $data = Notification::where('notifiable_id', auth()->id())->delete();

            // Kembalikan response sukses
            return $this->sendResponse($data, 'Notification clear successfully.');
        } catch (\Exception $e) {
            return $this->sendError(
                'An error occurred while processing your request.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function show(string $id)
    {
        try {
            // Cari notifikasi berdasarkan ID (UUID)
            $data = Notification::where('id', $id)->firstOrFail();

            // Tandai sebagai sudah dibaca
            if (is_null($data->read_at)) {
                $data->read_at = now(); // Menggunakan helper Laravel untuk waktu sekarang
                $data->save();
            }

            // Kembalikan response sukses
            return $this->sendResponse($data, 'Notification marked as read successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Jika notifikasi tidak ditemukan
            return $this->sendError('Notification not found.', [], 404);
        } catch (\Exception $e) {
            // Penanganan error lainnya
            return $this->sendError(
                'An unexpected error occurred while processing your request.',
                ['error' => $e->getMessage()],
                500
            );
        }

    }

}
