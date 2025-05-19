<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AssetsController extends Controller
{
    public function index($folder, $filename)
    {
        try {
            // Validasi folder & filename agar hanya mengandung karakter yang diizinkan
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $folder) || !preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
                return response()->json([
                    'message' => 'Invalid folder or file name',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Ambil konfigurasi disk
            $disk = env('FILESYSTEM_DISK', 'public');
            $basepath = config('app.basepath');
            $path = "$basepath/$folder/$filename";

            // // Periksa apakah file ada di storage
            if (!Storage::disk($disk)->exists($path)) {
                return response()->json([
                    'message' => 'File not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // // Ambil stream dari storage
            $stream = Storage::disk($disk)->readStream($path);
            if (!$stream) {
                return response()->json([
                    'message' => 'Failed to open file',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // // Dapatkan MIME type yang benar
            $mimeType = Storage::disk($disk)->mimeType($path) ?? 'application/octet-stream';

            // // Stream file ke client
            return response()->stream(function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, Response::HTTP_OK, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            ]);

        } catch (\Exception $e) {
            // dd($e);
            \Log::error('File streaming error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
