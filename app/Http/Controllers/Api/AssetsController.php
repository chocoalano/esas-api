<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AssetsController extends Controller
{
    public function index($folder, $filename)
    {
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            return response()->json([
                'message' => 'Invalid file name',
            ], Response::HTTP_BAD_REQUEST);
        }
        $path = "$folder/$filename";
        $disk = env('FILESYSTEM_DISK', 'public');
        if (Storage::disk($disk)->exists($path)) {
            $filePath = Storage::disk($disk)->path($path);
            $mimeType = Storage::disk($disk)->mimeType($path) ?? 'application/octet-stream';
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
            ]);
        }
        return response()->json([
            'message' => 'File not found',
        ], Response::HTTP_NOT_FOUND);
    }
}
