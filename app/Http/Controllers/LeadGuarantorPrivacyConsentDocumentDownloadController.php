<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Services\LeadPrivacyConsentPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadGuarantorPrivacyConsentDocumentDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        Lead $lead,
        LeadGuarantor $guarantor,
        LeadPrivacyConsentPdfService $leadPrivacyConsentPdfService,
    ): StreamedResponse {
        $user = $request->user();

        abort_unless($user?->can('view', $lead), 403);
        abort_unless($guarantor->lead_id === $lead->id, 404);

        if ($guarantor->privacy_consent_accepted_at === null) {
            $guarantor->forceFill(['privacy_consent_accepted_at' => now()])->save();
        }

        $companyKey = $request->query('company');

        if ($companyKey !== null && ! Lead::isValidPrivacyConsentCompany((string) $companyKey)) {
            abort(404);
        }

        $document = $leadPrivacyConsentPdfService->buildGuarantorDownload($guarantor, $companyKey);

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
}
