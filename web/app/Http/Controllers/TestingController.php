<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestingController extends Controller
{
    public function index()
    {

        exit('here in');
        // how to add file to minio using laravel

        // how to get file from minio using laravel

        // how to get file url from minio steream wise

        // dispatch job to rabbitmq
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|max:10240', // Validate file type and size (10MB max)
        ]);

        $file = $request->file('csv_file');

        // Upload file to MinIO with proper content
        $disk = Storage::disk('minio');
        
        // Use file contents to preserve original file type
        $fileContents = file_get_contents($file->getRealPath());
        $fileName = $file->getClientOriginalName();
        
        $disk->put('uploads/' . $fileName, $fileContents);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ]);
    }

}

