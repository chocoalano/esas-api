<?php
namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class UploadFile
{
    public static function uploadToSpaces(
        UploadedFile $file,
        string $directory = '',
        ?string $customFileName = null
    ) {
        try {
            // Tentukan folder berdasarkan environment
            $envFolder = config('app.debug') ? 'deployment' : 'production';
            // Ambil ekstensi file
            $extension = $file->getClientOriginalExtension();
            // Gunakan nama custom jika ada, jika tidak, pakai nama asli yang di-slug + waktu
            $baseName = $customFileName
                ? Str::slug(pathinfo($customFileName, PATHINFO_FILENAME))
                : time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $fileName = "{$baseName}.{$extension}";
            // Susun path folder
            $directory = trim($directory, '/'); // Bersihkan slash di awal/akhir
            $pathDirectory = "esas-assets/{$envFolder}/{$directory}";
            $filePath = "{$pathDirectory}/{$fileName}";
            // Upload ke storage
            Storage::disk('spaces')->putFileAs($pathDirectory, $file, $fileName, [
                'ACL' => 'public-read',
            ]);
            // Dapatkan URL file
            $fileUrl = Storage::disk('spaces')->url($filePath);
            return [
                'url' => $fileUrl,
                'path' => $filePath,
                'filename' => $fileName,
            ];
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    public static function removeFromSpaces(string $filePath)
    {
        try {
            $storage = Storage::disk('spaces');
            if ($storage->exists($filePath)) {
                $storage->delete($filePath);
                return true;
            } else {
                Log::warning("File not found on Spaces: {$filePath}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete file from Spaces: " . $e->getMessage());
            return false;
        }
    }

    public static function uploadWithResize($image, $folder, ?string $rename = null)
    {
        try {
            // Tentukan penyimpanan berdasarkan konfigurasi (lebih fleksibel)
            $disk = env('FILESYSTEM_DISK');

            // Pastikan direktori penyimpanan tersedia
            Storage::disk($disk)->makeDirectory($folder);

            // Tentukan nama file unik
            $filename = $rename ? "$rename.png" : now()->format('YmdHis') . ".png";

            // Baca gambar dengan Intervention Image
            $image = Image::read($image);

            // Simpan gambar utama dengan kualitas 90%
            $mainPath = Storage::disk($disk)->path("$folder/$filename");
            $image->save($mainPath, 50);
            return "$folder/$filename";
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal mengupload gambar: ' . $e->getMessage()]);
        }
    }
    public static function uploadAttachment($file, $folder)
    {
        // Pastikan parameter $file adalah file yang valid
        if (!$file || !$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file provided.');
        }

        // Generate nama file dengan format unik (timestamp + nama asli file)
        $timestamp = now()->timestamp;
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $sanitizedOriginalName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName); // Hindari karakter khusus
        $filename = "{$timestamp}_{$sanitizedOriginalName}.{$extension}";

        // Pastikan folder tidak memiliki slash di awal atau akhir
        $folder = trim($folder, '/');

        // Simpan file di dalam folder yang ditentukan
        try {
            $disk = Storage::disk(env('FILESYSTEM_DISK', 'local'));
            $disk->putFileAs($folder, $file, $filename);

            // Return full relative path in "$folder/$filename" format
            return "$folder/$filename";
        } catch (\Exception $e) {
            // Tangani kesalahan penyimpanan file
            throw new \RuntimeException("Failed to upload the file: " . $e->getMessage());
        }

    }

    public static function unlink($filename)
    {
        try {
            $disk = Storage::disk(env('FILESYSTEM_DISK'));
            if ($disk->exists($filename)) {
                $disk->delete($filename);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
