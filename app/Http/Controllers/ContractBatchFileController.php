<?php

namespace App\Http\Controllers;

use App\Models\ContractBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractBatchFileController extends Controller
{
    public function downloadArchive(Request $request, ContractBatch $contractBatch): StreamedResponse
    {
        abort_unless($request->user()?->can('view', $contractBatch), 403);
        abort_unless($contractBatch->archiveExists(), 404);

        return Storage::disk('legal')->download(
            $contractBatch->archive_path,
            $contractBatch->archive_file_name ?: 'dogovori.zip',
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ],
        );
    }

    public function downloadDocument(Request $request, ContractBatch $contractBatch, string $documentKey): StreamedResponse
    {
        abort_unless($request->user()?->can('view', $contractBatch), 403);

        $document = $contractBatch->findGeneratedDocument($documentKey);

        abort_unless($document !== null, 404);
        abort_unless($document['is_available'] ?? false, 404);

        return Storage::disk('legal')->download(
            $document['path'],
            $document['download_name'] ?? basename((string) $document['path']),
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ],
        );
    }
}
