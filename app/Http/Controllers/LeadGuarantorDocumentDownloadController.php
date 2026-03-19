<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadGuarantorDocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Lead $lead, LeadGuarantor $guarantor): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user?->can('view', $lead), 403);
        abort_unless($guarantor->lead_id === $lead->id, 404);

        $path = trim((string) $request->query('path'));

        abort_if(blank($path), 404);

        $document = $guarantor->findDocumentDownload($path);

        abort_if(($document === null) || (! $document['is_available']), 404);

        return Storage::disk('local')->download($document['path'], $document['name'], [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
