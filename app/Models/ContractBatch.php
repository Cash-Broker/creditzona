<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ContractBatch extends Model
{
    use HasFactory;

    public const DOCUMENT_VARIANT_PDF = 'pdf';

    public const DOCUMENT_VARIANT_DOCX = 'docx';

    public const DOCUMENT_TYPE_APPLICATION_REQUEST = 'application_request';

    public const DOCUMENT_TYPE_MEDIATION_AGREEMENT = 'mediation_agreement';

    public const DOCUMENT_TYPE_MEDIATION_PROTOCOL = 'mediation_protocol';

    public const DOCUMENT_TYPE_CONSULTATION_AGREEMENT = 'consultation_agreement';

    public const DOCUMENT_TYPE_CONSULTATION_PROTOCOL = 'consultation_protocol';

    public const DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE = 'company_promissory_note';

    public const DOCUMENT_TYPE_LOAN_AGREEMENT = 'loan_agreement';

    public const DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE = 'co_applicant_promissory_note';

    public const DOCUMENT_TYPE_DECLARATION = 'declaration';

    public const DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M = 'consultation_agreement_12m';

    public const DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION = 'credit_history_declaration';

    public const DOCUMENT_LAYOUT_FULL = 'full';

    public const DOCUMENT_LAYOUT_SIMPLIFIED = 'simplified';

    public const DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR = 'simplified_no_guarantor';

    public const DOCUMENT_LAYOUT_LOAN_ONLY = 'loan_only';

    public const DOCUMENT_LAYOUT_CONTRACT_12M = 'contract_12m';

    public const DOCUMENT_LAYOUT_BRIDGE_CREDIT = 'bridge_credit';

    public const COMPANY_REKREDO_KONSULT_DPK = 'rekredo_konsult_dpk';

    public const COMPANY_D_CONSULTING_EOOD = 'd_consulting_eood';

    public const HISTORY_FILE_COMBINED_PDF = 'combined-pdf';

    public const HISTORY_FILE_COMBINED_DOCX = 'combined-docx';

    public const HISTORY_FILE_ARCHIVE = 'archive';

    protected $fillable = [
        'lead_id',
        'company_key',
        'document_layout',
        'client_full_name',
        'client_city',
        'co_applicant_full_name',
        'request_date',
        'selected_document_types',
        'input_payload',
        'generated_documents',
        'generated_document_history',
        'combined_pdf_path',
        'combined_pdf_file_name',
        'combined_docx_path',
        'combined_docx_file_name',
        'archive_path',
        'archive_file_name',
        'generated_at',
        'created_by_user_id',
        'attached_user_id',
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
            self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M,
            self::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
            self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
            self::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
            self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
            self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            self::DOCUMENT_TYPE_LOAN_AGREEMENT,
            self::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
            self::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
            self::DOCUMENT_TYPE_DECLARATION,
        ];
    }

    public static function getDocumentTypeOptions(): array
    {
        return [
            self::DOCUMENT_TYPE_APPLICATION_REQUEST => 'Молба за предоставяне на консултативна услуга и посредничество',
            self::DOCUMENT_TYPE_MEDIATION_AGREEMENT => 'Договор за посредничество',
            self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT => 'Договор за финансова консултантска услуга',
            self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M => 'Договор за финансова консултация (12 месеца)',
            self::DOCUMENT_TYPE_MEDIATION_PROTOCOL => 'Приемо-предавателен протокол към договор за посредничество',
            self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL => 'Протокол за извършена консултация',
            self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE => 'Запис на заповед към фирмата',
            self::DOCUMENT_TYPE_LOAN_AGREEMENT => 'Договор за паричен заем',
            self::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE => 'Запис на заповед между клиент и съкредитоискател',
            self::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION => 'Декларация (трудова заетост и кредитна история)',
            self::DOCUMENT_TYPE_DECLARATION => 'Декларация',
        ];
    }

    public static function getDocumentTypeLabel(?string $documentType): string
    {
        return self::getDocumentTypeOptions()[$documentType] ?? ($documentType ?: 'Няма');
    }

    /**
     * @return array<string, string>
     */
    public static function getLayoutOptions(): array
    {
        return [
            self::DOCUMENT_LAYOUT_FULL => 'Пълен',
            self::DOCUMENT_LAYOUT_SIMPLIFIED => 'Опростен',
            self::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR => 'Опростен договор без поръчител',
            self::DOCUMENT_LAYOUT_LOAN_ONLY => 'Договор за Заем + Заповед',
            self::DOCUMENT_LAYOUT_CONTRACT_12M => 'Договор 12м',
            self::DOCUMENT_LAYOUT_BRIDGE_CREDIT => 'Мостов кредит',
        ];
    }

    public static function getLayoutLabel(?string $layout): string
    {
        return self::getLayoutOptions()[$layout] ?? ($layout ?: 'Няма');
    }

    /**
     * @return array<int, string>
     */
    public static function getDocumentTypesForLayout(string $layout): array
    {
        return match ($layout) {
            self::DOCUMENT_LAYOUT_LOAN_ONLY => [
                self::DOCUMENT_TYPE_LOAN_AGREEMENT,
                self::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
            ],
            self::DOCUMENT_LAYOUT_SIMPLIFIED => [
                self::DOCUMENT_TYPE_APPLICATION_REQUEST,
                self::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
                self::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
                self::DOCUMENT_TYPE_DECLARATION,
            ],
            self::DOCUMENT_LAYOUT_SIMPLIFIED_NO_GUARANTOR => [
                self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
                self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
            ],
            self::DOCUMENT_LAYOUT_CONTRACT_12M => [
                self::DOCUMENT_TYPE_APPLICATION_REQUEST,
                self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT_12M,
                self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                self::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                self::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
                self::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
                self::DOCUMENT_TYPE_DECLARATION,
            ],
            self::DOCUMENT_LAYOUT_FULL,
            self::DOCUMENT_LAYOUT_BRIDGE_CREDIT => [
                self::DOCUMENT_TYPE_APPLICATION_REQUEST,
                self::DOCUMENT_TYPE_MEDIATION_AGREEMENT,
                self::DOCUMENT_TYPE_CONSULTATION_AGREEMENT,
                self::DOCUMENT_TYPE_MEDIATION_PROTOCOL,
                self::DOCUMENT_TYPE_CONSULTATION_PROTOCOL,
                self::DOCUMENT_TYPE_COMPANY_PROMISSORY_NOTE,
                self::DOCUMENT_TYPE_LOAN_AGREEMENT,
                self::DOCUMENT_TYPE_CO_APPLICANT_PROMISSORY_NOTE,
                self::DOCUMENT_TYPE_DECLARATION,
            ],
            default => [],
        };
    }

    public static function getGeneratedDocumentLabel(string $documentType, ?int $copyNumber = null): string
    {
        $label = self::getDocumentTypeLabel($documentType);

        if ($copyNumber === null) {
            return $label;
        }

        if ($documentType === self::DOCUMENT_TYPE_LOAN_AGREEMENT) {
            return $label.' - екземпляр '.$copyNumber;
        }

        if ($documentType === self::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION) {
            $copyLabel = $copyNumber === 1 ? 'Възложител' : 'Поръчител';

            return $label.' - '.$copyLabel;
        }

        return $label;
    }

    /**
     * @return array<int, string>
     */
    public static function getGenerationOrderForLayout(?string $layout): array
    {
        if ($layout === null) {
            return self::getDocumentGenerationOrder();
        }

        $layoutOrder = self::getDocumentTypesForLayout($layout);

        return $layoutOrder !== [] ? $layoutOrder : self::getDocumentGenerationOrder();
    }

    /**
     * @return array<int, string>
     */
    public static function orderSelectedDocumentTypes(array $documentTypes, ?string $layout = null): array
    {
        $selected = array_values(array_unique(array_filter(
            $documentTypes,
            static fn (mixed $value): bool => is_string($value) && array_key_exists($value, self::getDocumentTypeOptions()),
        )));

        $priority = array_flip(self::getGenerationOrderForLayout($layout));

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
            self::DOCUMENT_TYPE_CREDIT_HISTORY_DECLARATION,
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
            'generated_document_history' => 'array',
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

    public function attachedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attached_user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeAttachedToUser(Builder $query, User $user): Builder
    {
        return $query->where('attached_user_id', $user->id);
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
            $document['variants'] = self::normalizeGeneratedDocumentVariants($document);
            $document['is_available'] = collect($document['variants'])
                ->contains(static fn (array $variant): bool => (bool) ($variant['is_available'] ?? false));

            if (blank($document['document_key'] ?? null) && filled($document['document_type'] ?? null)) {
                $document['document_key'] = $document['document_type'];
            }

            return $document;
        }, $documents));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findGeneratedDocument(string $documentKey, string $format = self::DOCUMENT_VARIANT_PDF): ?array
    {
        foreach ($this->getGeneratedDocumentsForDisplay() as $document) {
            if (($document['document_key'] ?? null) === $documentKey) {
                $variant = $document['variants'][$format] ?? null;

                if (is_array($variant)) {
                    return array_merge($document, $variant, [
                        'format' => $format,
                    ]);
                }

                return null;
            }
        }

        foreach ($this->getGeneratedDocumentsForDisplay() as $document) {
            if (($document['document_type'] ?? null) === $documentKey) {
                $variant = $document['variants'][$format] ?? null;

                if (is_array($variant)) {
                    return array_merge($document, $variant, [
                        'format' => $format,
                    ]);
                }

                return null;
            }
        }

        return null;
    }

    /**
     * Архивирани версии на генерираните документи, най-новата първа.
     * Всеки запис носи 'version' — индексът в колоната, използван за сваляне.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getGeneratedDocumentHistoryForDisplay(): array
    {
        $entries = [];

        foreach ($this->generated_document_history ?? [] as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $entries[] = [
                'version' => $index,
                'generated_at' => self::parseHistoryTimestamp($entry['generated_at'] ?? null),
                'combined_pdf_available' => self::historyPathExists($entry['combined_pdf_path'] ?? null),
                'combined_docx_available' => self::historyPathExists($entry['combined_docx_path'] ?? null),
                'archive_available' => self::historyPathExists($entry['archive_path'] ?? null),
            ];
        }

        return array_reverse($entries);
    }

    /**
     * @return array{path: string, download_name: string}|null
     */
    public function findHistoryFile(int $version, string $kind): ?array
    {
        $entry = ($this->generated_document_history ?? [])[$version] ?? null;

        if (! is_array($entry)) {
            return null;
        }

        [$pathKey, $nameKey, $fallbackName] = match ($kind) {
            self::HISTORY_FILE_COMBINED_PDF => ['combined_pdf_path', 'combined_pdf_file_name', 'dogovori.pdf'],
            self::HISTORY_FILE_COMBINED_DOCX => ['combined_docx_path', 'combined_docx_file_name', 'dogovori.docx'],
            self::HISTORY_FILE_ARCHIVE => ['archive_path', 'archive_file_name', 'dogovori.zip'],
            default => [null, null, null],
        };

        if ($pathKey === null) {
            return null;
        }

        $path = $entry[$pathKey] ?? null;

        if (! is_string($path) || ! self::historyPathExists($path)) {
            return null;
        }

        $downloadName = $entry[$nameKey] ?? null;

        return [
            'path' => $path,
            'download_name' => is_string($downloadName) && filled($downloadName) ? $downloadName : $fallbackName,
        ];
    }

    private static function historyPathExists(mixed $path): bool
    {
        return is_string($path) && filled($path) && Storage::disk('legal')->exists($path);
    }

    private static function parseHistoryTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone('Europe/Sofia');
        } catch (Throwable) {
            return null;
        }
    }

    public function combinedPdfExists(): bool
    {
        if (blank($this->combined_pdf_path)) {
            return false;
        }

        return Storage::disk('legal')->exists($this->combined_pdf_path);
    }

    public function combinedDocxExists(): bool
    {
        if (blank($this->combined_docx_path)) {
            return false;
        }

        return Storage::disk('legal')->exists($this->combined_docx_path);
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
            self::orderSelectedDocumentTypes(
                $this->selected_document_types ?? [],
                $this->document_layout ?? null,
            ),
        );
    }

    public function deleteStoredFiles(): void
    {
        $paths = [
            ...self::collectGeneratedDocumentPaths($this->generated_documents ?? []),
            $this->combined_pdf_path,
            $this->combined_docx_path,
            $this->archive_path,
        ];

        foreach ($this->generated_document_history ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $entryDocuments = is_array($entry['generated_documents'] ?? null) ? $entry['generated_documents'] : [];

            $paths = [
                ...$paths,
                ...self::collectGeneratedDocumentPaths($entryDocuments),
                $entry['combined_pdf_path'] ?? null,
                $entry['combined_docx_path'] ?? null,
                $entry['archive_path'] ?? null,
            ];
        }

        $directories = [];

        foreach ($paths as $path) {
            if (! is_string($path) || blank($path)) {
                continue;
            }

            $directories[] = dirname($path);
            Storage::disk('legal')->delete($path);
        }

        foreach (array_unique($directories) as $directory) {
            Storage::disk('legal')->deleteDirectory($directory);
        }
    }

    /**
     * @param  array<int, mixed>  $documents
     * @return array<int, string>
     */
    private static function collectGeneratedDocumentPaths(array $documents): array
    {
        $paths = [];

        foreach ($documents as $document) {
            if (! is_array($document)) {
                continue;
            }

            foreach (self::normalizeGeneratedDocumentVariants($document) as $variant) {
                $path = $variant['path'] ?? null;

                if (is_string($path) && filled($path)) {
                    $paths[] = $path;
                }
            }
        }

        return $paths;
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

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, array<string, mixed>>
     */
    private static function normalizeGeneratedDocumentVariants(array $document): array
    {
        $variants = $document['variants'] ?? null;

        if (is_array($variants) && $variants !== []) {
            return collect($variants)
                ->filter(static fn (mixed $variant): bool => is_array($variant))
                ->mapWithKeys(static function (array $variant, string $format): array {
                    $path = $variant['path'] ?? null;

                    return [
                        $format => array_merge($variant, [
                            'format' => $format,
                            'is_available' => filled($path)
                                ? Storage::disk('legal')->exists($path)
                                : false,
                        ]),
                    ];
                })
                ->all();
        }

        $path = $document['path'] ?? null;

        if (! filled($path)) {
            return [];
        }

        return [
            self::DOCUMENT_VARIANT_PDF => [
                'format' => self::DOCUMENT_VARIANT_PDF,
                'path' => $path,
                'download_name' => $document['download_name'] ?? basename((string) $path),
                'mime_type' => $document['mime_type'] ?? 'application/pdf',
                'file_size' => $document['file_size'] ?? null,
                'is_available' => Storage::disk('legal')->exists($path),
            ],
        ];
    }
}
