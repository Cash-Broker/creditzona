<?php

namespace App\Services;

use App\Models\Lead;
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
            $this->formatApplicantIdentity(
                (string) $lead->first_name,
                (string) $lead->last_name,
                (string) $lead->phone,
                (string) $lead->email,
                (string) $lead->city,
            ),
        ));

        return [
            'path' => $path,
            'name' => Lead::getPrivacyConsentDocumentName(),
        ];
    }

    private function buildPdf(string $applicantIdentity): string
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
                $this->writeApplicantIdentity($mpdf, $applicantIdentity);

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

    private function writeApplicantIdentity(Mpdf $mpdf, string $applicantIdentity): void
    {
        $mpdf->WriteFixedPosHTML(
            sprintf(
                '<div style="font-family: dejavuserif; font-size: %.1Fpt; line-height: 1; white-space: nowrap;">%s</div>',
                self::FIELD_FONT_SIZE,
                e($applicantIdentity),
            ),
            self::FIELD_X,
            self::FIELD_Y,
            self::FIELD_MAX_WIDTH,
            self::FIELD_HEIGHT,
            'visible',
        );
    }

    private function formatApplicantIdentity(
        string $firstName,
        string $lastName,
        string $phone,
        string $email,
        string $city,
    ): string {
        return trim(sprintf(
            '%s %s, тел. %s, %s, гр. %s',
            $firstName,
            $lastName,
            $phone,
            $email,
            $city,
        ));
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
