<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioStorageService
{
    /**
     * Store an uploaded file to MinIO and return metadata about the stored object.
     *
     * @return array{disk:string,bucket:?string,path:string,object_url:string,filename:string,mime_type:?string,size:int,original_name:string,stored_at:string}
     */
    public function storeUploadedFile(UploadedFile $file, string $prefix = 'gl-uploads'): array
    {
        $disk = 'minio';
        $bucket = config('filesystems.disks.minio.bucket');
        $directory = trim($prefix, '/') . '/' . date('Y/m/d');

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBaseName = trim(Str::slug((string)$baseName), '-_');
        if ($safeBaseName === '') {
            $safeBaseName = 'upload';
        }
        $uuid = (string) Str::uuid();
        $filename = $safeBaseName . '_' . $uuid . ($extension ? ('.' . $extension) : '');

        // putFileAs will stream the file contents to the disk without loading entire file into memory
        Storage::disk($disk)->putFileAs($directory, $file, $filename);

        $path = $directory . '/' . $filename;

        // Build object URL in path-style format: {endpoint}/{bucket}/{path}
        $endpoint = (string) config('filesystems.disks.minio.endpoint');
        $endpoint = rtrim($endpoint, '/');
        $objectUrl = $endpoint . '/' . $bucket . '/' . ltrim($path, '/');

        return [
            'disk' => $disk,
            'bucket' => $bucket,
            'path' => $path,
            'object_url' => $objectUrl,
            'filename' => $filename,
            'mime_type' => $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
            'original_name' => $originalName,
            'stored_at' => now()->toISOString(),
        ];
    }
}
