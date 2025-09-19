<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class MinIOController extends Controller
{
    public function store()
    {
        // sample minio store function

        // Upload a file
        $path = Storage::disk('minio')->put('uploads', $request->file('photo'));

        // Download/get file content
        $content = Storage::disk('minio')->get('uploads/filename.jpg');

        // Generate a temporary URL (expires in 1 hour)
        $url = Storage::disk('minio')->temporaryUrl('uploads/filename.jpg', now()->addHour());

        // Check if file exists
        $exists = Storage::disk('minio')->exists('uploads/filename.jpg');

        // Delete a file
        Storage::disk('minio')->delete('uploads/filename.jpg');

        // List files
        $files = Storage::disk('minio')->files('uploads');

    }
}
