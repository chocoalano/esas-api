<?php
use App\Http\Controllers\Api\AssetsController;
use App\Support\UploadFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

Route::get('/assets/{folder}/{filename}', [AssetsController::class, 'index']);
Route::post('/upload', function (Request $request) {
    $request->validate([
        'file' => 'required|file|max:5120',
    ]);

    $file = $request->file('file');
    $directory = 'esas-assets/deployment/avatars';
    $upload = UploadFile::uploadToSpaces($file, $directory);
    return $upload;
})->middleware('logger');
require __DIR__.'/mobile.php';
require __DIR__.'/dekstop.php';
