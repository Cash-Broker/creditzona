<?php

namespace App\Http\Controllers;

use App\Models\AdminDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDocumentFileController extends Controller
{
    public function open(Request $request, AdminDocument $adminDocument): StreamedResponse
    {
        abort_unless($request->user()?->can('view', $adminDocument), 403);
        abort_unless($adminDocument->fileExists(), 404);
        abort_unless($adminDocument->canBeOpenedInline(), 403);

        return Storage::disk('local')->response(
            $adminDocument->file_path,
            $adminDocument->original_file_name,
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Content-Type' => $adminDocument->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function download(Request $request, AdminDocument $adminDocument): StreamedResponse
    {
        abort_unless($request->user()?->can('view', $adminDocument), 403);
        abort_unless($adminDocument->fileExists(), 404);

        return Storage::disk('local')->download(
            $adminDocument->file_path,
            $adminDocument->original_file_name,
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ],
        );
    }
}
