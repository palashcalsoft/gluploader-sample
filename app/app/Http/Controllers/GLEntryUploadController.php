<?php

namespace App\Http\Controllers;

use App\Services\GLEntryUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GLEntryUploadController extends Controller
{
    public function __construct(private readonly GLEntryUploadService $service)
    {
    }

    /**
     * Handle the uploaded CSV and return validation results.
     *
     * Keeps request validation and delegates processing to service.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'loft_username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Placeholder authentication against Loft credentials.
        // TODO: Replace with real Loft API auth if available.
        if (trim($request->input('loft_username')) === '' || trim($request->input('password')) === '') {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $file = $request->file('file');
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $uploadedBy = $user ? $user->email : 'system';

        $result = $this->service->process($file, (string)$request->input('loft_username'), $uploadedBy);
        $httpCode = $result['http_code'] ?? 200;
        unset($result['http_code']);
        return response()->json($result, $httpCode);
    }
}


