<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContractBatch extends Model
{
    use HasFactory;

    public const DOCUMENT_TYPE_APPLICATION_REQUEST = 'application_request';

    public const DOCUMENT_TYPE_MEDIATION_AGREEMENT = 'mediation_agreement';

    public const DOCUMENT_TYPE_MEDIATION_PROTOCOL = 'mediation_protocol';

    public const DOCUMENT_TYPE_CONSULTATION_AGREEMENT = 'consultation_agreement';

    public const DOCUMENT_TYPE_CONSULTATION_PROTOCOL = 'consultation_protocol';

    public const DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE = 'company_promissory_note';

    public const DOCUMENT_TYPE_LOAN_AGREEMENT = 'loan_agreement';

    public const DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE = 'co_applicant_promissory_note';

    public const DOCUMENT_TYPE_DECLARATION = 'declaration';

    public const COMPANY_REKREDO_KONSULT_DPK = 'rekredo_konsult_dpk';

    public const COMPANY_D_CONSULTING_EOOD = 'd_consulting_eood';

    protected $fillable = [
        'lead_id',
        'company_key',
        'client_full_name',
        'co_applicant_full_name',
        'request_date',
        'selected_document_types',
        'input_payload',
        'generated_documents',
        'archive_path',
        'archive_file_name',
        'generated_at',
        'created_by_user_id',
    ];

    public static function getCompanyDefinitions(): array
    {
        return [
            self::COMPANY_REKREDO_KONSULT_DPK => [
                'name' => 'РеКредо Консулт ДПК',
                'eik' => '208669746',
                'address' => 'гр. Пловдив, р-н Северен, ул. "Полковник Сава Муткуров" № 30, ет. 1, оф. 2',
                'representative_name' => 'Елена Стефанова',
                'representative_title' => 'юрисконсулт',
            ],
            self::COMPANY_D_CONSULTING_EOOD => [
                'name' => 'Д – Консултинг ЕООД',
                'eik' => '208105051',
                'address' => 'гр. Пловдив, р-н Северен, ул. "Полковник Сава Муткуров" № 38, вход А, ет. 3, ап. 7',
                'representative_name' => 'Анна Манолова',
                'representative_title' => 'пълномощник',
            ],
        ];
    }

    public static function getDocumentGenerationOrder(): array
    {
        return [
            self::DOCUMENT_TYPE_APPLICATION_REQUEST,
            self::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
            self::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            self::DOCUMENT_TYPE_LOAN_AGREEMENT,
            self::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
            self::DOCUMENT_TYPE_DECLARATION,
        ];
    }

    public static function getDocumentTypeOptions(): array
    {
        return [
            self::DOCUMENT_TYPE_APPLICATION_REQUEST => 'Молба за предоставяне на консултативна услуга и посредничество',
            self::DOCUMENT_TYPE_MEDIATION_AGREEMENT => 'Договор за посредничество',
            self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT => 'Договор за финансова консултантска услуга',
            self::DOCUMENT_TYPE_MEDIATION_PROTOCOL => 'Приемо-предавателен протокол към договор за посредничество',
            self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL => 'Протокол за извършена консултация',
            self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE => 'Запис на заповед към фирмата',
            self::DOCUMENT_TYPE_LOAN_AGREEMENT => 'Договор за паричен заем',
            self::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE => 'Запис на заповед между клиент и съкредитоискател',
            self::DOCUMENT_TYPE_DECLARATION => 'Декларация',
        ];
    }

    public static function getDocumentTypeLabel(?string $documentType): string
    {
        return self::getDocumentTypeOptions()[$documentType] ?? ($documentType ?: 'Няма');
    }

    public static function getGeneratedDocumentLabel(string $documentType, ?int $copyNumber = null): string
    {
        $label = self::getDocumentTypeLabel($documentType);

        if ($documentType !== self::DOCUMENT_TYPE_LOAN_AGREEMENT || $copyNumber === null) {
            return $label;
        }

        return $label.' - екземпляр '.$copyNumber;
    }

    /**
     * @return array<int, string>
     */
    public static function orderSelectedDocumentTypes(array $documentTypes): array
    {
        $selected = array_values(array_unique(array_filter(
            $documentTypes,
            static fn (mixed $value): bool => is_string($value) && array_key_exists($value, self::getDocumentTypeOptions()),
        )));

        $priority = array_flip(self::getDocumentGenerationOrder());

        usort($selected, static function (string $left, string $right) use ($priority): int {
            return ($priority[$left] ?? PHP_INT_MAX) <=> ($priority[$right] ?? PHP_INT_MAX);
        });

        return $selected;
    }

    public static function buildGeneratedDocumentKey(string $documentType, ?int $copyNumber = null): string
    {
        if ($copyNumber === null) {
            return $documentType;
        }

        return $documentType.'_copy_'.$copyNumber;
    }

    /**
     * @return array<int, string>
     */
    public static function getCoApplicantDocumentTypes(): array
    {
        return [
            self::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            self::DOCUMENT_TYPE_LOAN_AGREEMENT,
            self::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
            self::DOCUMENT_TYPE_DECLARATION,
        ];
    }

    public static function getCompanyOptions(): array
    {
        return collect(self::getCompanyDefinitions())
            ->mapWithKeys(static fn (array $company, string $key): array => [$key => $company['name']])
            ->all();
    }

    public static function getCompanyData(string $companyKey): array
    {
        return self::getCompanyDefinitions()[$companyKey] ?? [];
    }

    public static function getCompanyLabel(?string $companyKey): string
    {
        $company = self::getCompanyData((string) $companyKey);

        return $company['name'] ?? ($companyKey ?: 'Няма');
    }

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'selected_document_types' => 'array',
            'input_payload' => 'encrypted:array',
            'generated_documents' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(static function (self $batch): void {
            $batch->deleteStoredFiles();
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function getDisplayTitle(): string
    {
        $requestDate = $this->request_date?->format('d.m.Y');

        return trim(implode(' - ', array_filter([
            $this->client_full_name,
            $requestDate,
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubmittedInput(): array
    {
        $payload = $this->input_payload ?? [];

        return is_array($payload['submitted'] ?? null) ? $payload['submitted'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDerivedInput(): array
    {
        $payload = $this->input_payload ?? [];

        return is_array($payload['derived'] ?? null) ? $payload['derived'] : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGeneratedDocumentsForDisplay(): array
    {
        $documents = $this->generated_documents ?? [];

        return array_values(array_map(static function (array $document): array {
            $document['is_available'] = filled($document['path'] ?? null)
                ? Storage::disk('legal')->exists($document['path'])
                : false;

            if (blank($document['document_key'] ?? null) && filled($document['document_type'] ?? null)) {
                $document['document_key'] = $document['document_type'];
            }

            return $document;
        }, $documents));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findGeneratedDocument(string $documentKey): ?array
    {
        foreach ($this->getGeneratedDocumentsForDisplay() as $document) {
            if (($document['document_key'] ?? null) === $documentKey) {
                return $document;
            }
        }

        foreach ($this->getGeneratedDocumentsForDisplay() as $document) {
            if (($document['document_type'] ?? null) === $documentKey) {
                return $document;
            }
        }

        return null;
    }

    public function archiveExists(): bool
    {
        if (blank($this->archive_path)) {
            return false;
        }

        return Storage::disk('legal')->exists($this->archive_path);
    }

    /**
     * @return array<int, string>
     */
    public function getSelectedDocumentTypeLabels(): array
    {
        return array_map(
            static fn (string $documentType): string => self::getDocumentTypeLabel($documentType),
            self::orderSelectedDocumentTypes($this->selected_document_types ?? []),
        );
    }

    public function deleteStoredFiles(): void
    {
        $directories = [];

        foreach ($this->generated_documents ?? [] as $document) {
            $path = is_array($document) ? ($document['path'] ?? null) : null;

            if (filled($path)) {
                $directories[] = dirname($path);
                Storage::disk('legal')->delete($path);
            }
        }

        if (filled($this->archive_path)) {
            $directories[] = dirname($this->archive_path);
            Storage::disk('legal')->delete($this->archive_path);
        }

        foreach (array_unique(array_filter($directories)) as $directory) {
            Storage::disk('legal')->deleteDirectory($directory);
        }
    }

    public static function maskEgn(?string $value): string
    {
        return Lead::maskEgn($value);
    }

    public static function maskDocumentNumber(?string $value): string
    {
        $normalized = preg_replace('/\s+/u', '', (string) $value);

        if (blank($normalized)) {
            return 'Няма';
        }

        if (mb_strlen($normalized) <= 4) {
            return str_repeat('*', mb_strlen($normalized));
        }

        return str_repeat('*', mb_strlen($normalized) - 4).mb_substr($normalized, -4);
    }
}
