<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use RuntimeException;

class LeadPrivacyConsentPdfService
{
    private const FIELD_X = 64.5;

    private const FIELD_Y = 55.0;

    private const FIELD_FONT_SIZE = 8.4;

    private const FIELD_MAX_WIDTH = 136.0;

    private const FIELD_HEIGHT = 7.0;

    public function storeSnapshot(Lead $lead): array
    {
        $path = sprintf(
            'lead-consents/%s/%s.pdf',
            now()->format('Y/m'),
            (string) Str::ulid(),
        );

        Storage::disk('local')->put($path, $this->buildPdf(
            $this->formatParticipantIdentity(
                $lead->first_name,
                $lead->middle_name,
                $lead->last_name,
                $lead->egn,
                $lead->phone,
                $lead->email,
                $lead->city,
            ),
        ));

        return [
            'path' => $path,
            'name' => Lead::getPrivacyConsentDocumentName(),
        ];
    }

    /**
     * @return array{content: string, download_name: string}
     */
    public function buildGuarantorDownload(LeadGuarantor $guarantor): array
    {
        return [
            'content' => $this->buildPdf($this->formatParticipantIdentity(
                $guarantor->first_name,
                $guarantor->middle_name,
                $guarantor->last_name,
                $guarantor->egn,
                $guarantor->phone,
                $guarantor->email,
                $guarantor->city,
            )),
            'download_name' => $guarantor->buildPrivacyConsentDownloadFileName(),
        ];
    }

    private function buildPdf(string $participantIdentity): string
    {
        $templatePath = public_path(Lead::getPrivacyConsentTemplatePath());

        if (! is_file($templatePath)) {
            throw new RuntimeException('Privacy consent PDF template is missing.');
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'tempDir' => $this->resolveTempDir(),
        ]);

        $pageCount = $mpdf->setSourceFile($templatePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $template = $mpdf->importPage($pageNumber);
            $templateSize = $mpdf->getTemplateSize($template);

            if ($pageNumber === 1) {
                $mpdf->useTemplate($template, 0, 0, null, null, true);
                $this->writeParticipantIdentity($mpdf, $participantIdentity);

                continue;
            }

            $mpdf->AddPageByArray([
                'orientation' => $templateSize['orientation'],
                'sheet-size' => [$templateSize['width'], $templateSize['height']],
            ]);
            $mpdf->useTemplate($template);
        }

        return $mpdf->OutputBinaryData();
    }

    private function writeParticipantIdentity(Mpdf $mpdf, string $participantIdentity): void
    {
        $mpdf->WriteFixedPosHTML(
            sprintf(
                '<div style="font-family: dejavuserif; font-size: %.1Fpt; line-height: 1; white-space: nowrap;">%s</div>',
                self::FIELD_FONT_SIZE,
                e($participantIdentity),
            ),
            self::FIELD_X,
            self::FIELD_Y,
            self::FIELD_MAX_WIDTH,
            self::FIELD_HEIGHT,
            'visible',
        );
    }

    private function formatParticipantIdentity(
        ?string $firstName,
        ?string $middleName,
        ?string $lastName,
        ?string $egn,
        ?string $phone,
        ?string $email,
        ?string $city,
    ): string {
        $name = trim(implode(' ', array_filter([
            $firstName,
            $middleName,
            $lastName,
        ])));

        return implode(', ', array_values(array_filter([
            $name !== '' ? $name : null,
            filled($egn) ? 'ЕГН '.$egn : null,
            filled($phone) ? 'тел. '.$phone : null,
            filled($email) ? $email : null,
            filled($city) ? 'гр. '.$city : null,
        ])));
    }

    private function resolveTempDir(): string
    {
        $tempDir = storage_path('app/private/mpdf-temp');

        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw new RuntimeException('Unable to create mPDF temp directory.');
        }

        return $tempDir;
    }
}
