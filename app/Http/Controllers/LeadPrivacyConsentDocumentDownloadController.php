<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\LeadPrivacyConsentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadPrivacyConsentDocumentDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        Lead $lead,
        LeadPrivacyConsentPdfService $leadPrivacyConsentPdfService,
    ): StreamedResponse {
        $user = $request->user();

        abort_unless($user?->can('view', $lead), 403);

        $companyKey = $request->query('company');

        if ($companyKey !== null) {
            abort_unless(Lead::isValidPrivacyConsentCompany((string) $companyKey), 404);

            $document = $leadPrivacyConsentPdfService->buildLeadDownload($lead, (string) $companyKey);

            return response()->streamDownload(
                static function () use ($document): void {
                    echo $document['content'];
                },
                $document['download_name'],
                [
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Content-Type' => 'application/pdf',
                ],
            );
        }

        $document = $lead->findPrivacyConsentDocumentDownload();

        abort_if(
            ($document === null) || (! $document['is_available']) || ($document['url'] !== null),
            404,
        );

        return Storage::disk('local')->download(
            $document['path'],
            $document['download_name'] ?? $document['name'],
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ],
        );
    }
}
