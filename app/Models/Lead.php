<?php

namespace App\Models;

use App\Support\Phone\PhoneNormalizer;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lead extends Model implements HasRichContent
{
    use InteractsWithRichContent;

    public const PRIVACY_CONSENT_TEMPLATE_PATH = 'documents/legal/lead-personal-data-consent-ready.pdf';

    public const PRIVACY_CONSENT_PUBLIC_DOCUMENT_PATH = 'documents/legal/lead-personal-data-consent-v1.pdf';

    public const PRIVACY_CONSENT_DOCUMENT_NAME = 'Съгласие за обработка на лични данни';

    public const PRIVACY_CONSENT_DOWNLOAD_FILE_NAME = 'lead-personal-data-consent.pdf';

    public const CREDIT_TYPE_CONSUMER = 'consumer';

    public const CREDIT_TYPE_MORTGAGE = 'mortgage';

    public const CREDIT_TYPE_CONSUMER_WITH_GUARANTOR = 'consumer_with_guarantor';

    public const MARITAL_STATUS_SINGLE = 'single';

    public const MARITAL_STATUS_MARRIED = 'married';

    public const MARITAL_STATUS_DIVORCED = 'divorced';

    public const MARITAL_STATUS_WIDOWED = 'widowed';

    public const MARITAL_STATUS_COHABITING = 'cohabiting';

    protected $fillable = [
        'credit_type',
        'first_name',
        'middle_name',
        'last_name',
        'egn',
        'phone',
        'normalized_phone',
        'email',
        'city',
        'workplace',
        'job_title',
        'salary',
        'marital_status',
        'children_under_18',
        'salary_bank',
        'credit_bank',
        'documents',
        'document_file_names',
        'internal_notes',
        'amount',
        'property_type',
        'property_location',
        'status',
        'assigned_user_id',
        'additional_user_id',
        'returned_additional_user_id',
        'returned_to_primary_at',
        'returned_to_primary_archived_user_id',
        'returned_to_primary_archived_at',
        'approved_returned_at',
        'approved_returned_by_user_id',
        'archived_additional_user_id',
        'attached_archived_at',
        'marked_for_later_at',
        'source',
        'utm_source',
        'utm_campaign',
        'utm_medium',
        'gclid',
        'privacy_consent_accepted',
        'privacy_consent_accepted_at',
        'privacy_consent_document_name',
        'privacy_consent_document_path',
    ];

    public static function getPrivacyConsentDocumentPath(): string
    {
        return self::PRIVACY_CONSENT_PUBLIC_DOCUMENT_PATH;
    }

    public static function getPrivacyConsentTemplatePath(): string
    {
        return self::PRIVACY_CONSENT_TEMPLATE_PATH;
    }

    public static function getPrivacyConsentDocumentName(): string
    {
        return self::PRIVACY_CONSENT_DOCUMENT_NAME;
    }

    public static function getPrivacyConsentDownloadFileName(): string
    {
        return self::PRIVACY_CONSENT_DOWNLOAD_FILE_NAME;
    }

    public function buildPrivacyConsentDownloadFileName(): string
    {
        $baseName = implode('', array_filter([
            $this->sanitizePrivacyConsentDownloadSegment($this->first_name),
            $this->sanitizePrivacyConsentDownloadSegment($this->last_name),
            preg_replace('/\D+/u', '', (string) ($this->phone ?? '')) ?: null,
        ]));

        if ($baseName === '') {
            $baseName = 'ДекларацияСъгласие';
        }

        return $baseName.'ДекларацияСъгласие.pdf';
    }

    public static function isPublicPrivacyConsentDocumentPath(string $path): bool
    {
        return str_starts_with(ltrim($path, '/'), 'documents/legal/');
    }

    public static function getPrivacyConsentDocumentUrl(?string $path = null): ?string
    {
        $documentPath = ltrim($path ?? self::PRIVACY_CONSENT_PUBLIC_DOCUMENT_PATH, '/');

        if (! static::isPublicPrivacyConsentDocumentPath($documentPath)) {
            return null;
        }

        $url = asset($documentPath);
        $absolutePath = public_path($documentPath);

        if (! is_file($absolutePath)) {
            return $url;
        }

        return $url.'?v='.filemtime($absolutePath);
    }

    /**
     * @return array{name: string, path: string, url: ?string, is_available: bool, download_name: string}
     */
    public static function getPrivacyConsentDocumentMeta(?string $path = null, ?string $name = null): array
    {
        $documentPath = ltrim($path ?? self::PRIVACY_CONSENT_PUBLIC_DOCUMENT_PATH, '/');
        $isPublicDocument = static::isPublicPrivacyConsentDocumentPath($documentPath);

        return [
            'name' => $name ?? self::PRIVACY_CONSENT_DOCUMENT_NAME,
            'path' => $documentPath,
            'url' => $isPublicDocument ? static::getPrivacyConsentDocumentUrl($documentPath) : null,
            'is_available' => $isPublicDocument
                ? is_file(public_path($documentPath))
                : Storage::disk('local')->exists($documentPath),
            'download_name' => static::getPrivacyConsentDownloadFileName(),
        ];
    }

    public static function getCreditTypeOptions(): array
    {
        return [
            self::CREDIT_TYPE_CONSUMER => 'Потребителски кредит',
            self::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR => 'Потребителски кредит с поръчител',
            self::CREDIT_TYPE_MORTGAGE => 'Ипотечен кредит',
        ];
    }

    public static function getPublicCreditTypeOptions(): array
    {
        return [
            self::CREDIT_TYPE_CONSUMER => 'Потребителски кредит',
            self::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR => 'Финансиране с поръчител',
            // self::CREDIT_TYPE_MORTGAGE => 'Ипотечен кредит',
        ];
    }

    public static function getCreditTypeLabel(?string $state): string
    {
        return static::getCreditTypeOptions()[$state] ?? ($state ?: 'Няма');
    }

    public static function getMaritalStatusOptions(): array
    {
        return [
            self::MARITAL_STATUS_SINGLE => 'Неженен/Неомъжена',
            self::MARITAL_STATUS_MARRIED => 'Женен/Омъжена',
            self::MARITAL_STATUS_DIVORCED => 'Разведен/а',
            self::MARITAL_STATUS_WIDOWED => 'Вдовец/Вдовица',
            self::MARITAL_STATUS_COHABITING => 'На семейни начала',
        ];
    }

    public static function getMaritalStatusLabel(?string $state): string
    {
        return static::getMaritalStatusOptions()[$state] ?? ($state ?: 'Няма');
    }

    public static function maskEgn(?string $value): string
    {
        if (blank($value)) {
            return 'Няма';
        }

        $normalized = preg_replace('/\D+/', '', $value) ?: $value;
        $visibleDigits = 4;

        if (strlen($normalized) <= $visibleDigits) {
            return str_repeat('*', strlen($normalized));
        }

        return str_repeat('*', strlen($normalized) - $visibleDigits).substr($normalized, -$visibleDigits);
    }

    /**
     * @return array<int, string>
     */
    public function getDocumentDisplayNames(): array
    {
        $fileNames = $this->document_file_names ?? [];

        if ($fileNames !== []) {
            return array_values($fileNames);
        }

        return array_map(
            static fn (string $path): string => basename($path),
            $this->documents ?? [],
        );
    }

    /**
     * @return array<int, array{name: string, path: string, is_available: bool}>
     */
    public function getDocumentDownloads(): array
    {
        $documentPaths = array_values(array_unique([
            ...array_filter(
                $this->documents ?? [],
                static fn (mixed $path): bool => is_string($path) && filled($path),
            ),
            ...array_filter(
                array_keys($this->document_file_names ?? []),
                static fn (mixed $path): bool => is_string($path) && filled($path),
            ),
        ]));

        if ($documentPaths === []) {
            return [];
        }

        $disk = Storage::disk('local');
        $fileNames = $this->document_file_names ?? [];

        return array_map(function (string $path) use ($disk, $fileNames): array {
            $isAvailable = $disk->exists($path);

            return [
                'name' => $fileNames[$path] ?? basename($path),
                'path' => $path,
                'is_available' => $isAvailable,
            ];
        }, $documentPaths);
    }

    public function hasPrivacyConsent(): bool
    {
        return (bool) $this->privacy_consent_accepted || $this->privacy_consent_accepted_at !== null;
    }

    /**
     * @return array<int, array{name: string, path: string, url: ?string, is_available: bool, download_name: string}>
     */
    public function getPrivacyConsentDocumentDownloads(): array
    {
        if (! $this->hasPrivacyConsent()) {
            return [];
        }

        return [
            array_merge(static::getPrivacyConsentDocumentMeta(
                $this->privacy_consent_document_path ?: static::getPrivacyConsentDocumentPath(),
                $this->privacy_consent_document_name ?: static::getPrivacyConsentDocumentName(),
            ), [
                'download_name' => $this->buildPrivacyConsentDownloadFileName(),
            ]),
        ];
    }

    /**
     * @return array{name: string, path: string, url: ?string, is_available: bool, download_name: string}|null
     */
    public function findPrivacyConsentDocumentDownload(?string $path = null): ?array
    {
        $expectedPath = filled($path)
            ? trim((string) $path)
            : ($this->privacy_consent_document_path ?: static::getPrivacyConsentDocumentPath());

        foreach ($this->getPrivacyConsentDocumentDownloads() as $document) {
            if ($document['path'] === $expectedPath) {
                return $document;
            }
        }

        return null;
    }

    /**
     * @return array{name: string, path: string, is_available: bool}|null
     */
    public function findDocumentDownload(string $path): ?array
    {
        foreach ($this->getDocumentDownloads() as $document) {
            if ($document['path'] === $path) {
                return $document;
            }
        }

        return null;
    }

    private function sanitizePrivacyConsentDownloadSegment(?string $value): ?string
    {
        $sanitized = preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $value);

        return filled($sanitized) ? $sanitized : null;
    }

    protected function casts(): array
    {
        return [
            'egn' => 'encrypted',
            'salary' => 'integer',
            'children_under_18' => 'integer',
            'documents' => 'array',
            'document_file_names' => 'array',
            'privacy_consent_accepted' => 'boolean',
            'privacy_consent_accepted_at' => 'datetime',
            'returned_to_primary_at' => 'datetime',
            'returned_to_primary_archived_at' => 'datetime',
            'approved_returned_at' => 'datetime',
            'attached_archived_at' => 'datetime',
            'marked_for_later_at' => 'datetime',
        ];
    }

    public function setPhoneAttribute(mixed $value): void
    {
        $normalizedPhone = PhoneNormalizer::normalize($value);

        $this->attributes['phone'] = $normalizedPhone;
        $this->attributes['normalized_phone'] = $normalizedPhone;
    }

    public function scopeForNormalizedPhone(Builder $query, string $phone): Builder
    {
        return $query->where(function (Builder $innerQuery) use ($phone): void {
            $innerQuery
                ->where('normalized_phone', $phone)
                ->orWhere(function (Builder $legacyQuery) use ($phone): void {
                    $legacyQuery
                        ->whereNull('normalized_phone')
                        ->where('phone', $phone);
                });
        });
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->where('assigned_user_id', $user->id)
                ->orWhere('additional_user_id', $user->id);
        });
    }

    public function scopeAttachedToUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('additional_user_id', $user->id)
            ->whereNull('attached_archived_at');
    }

    public function scopeAttachedArchiveForUser(Builder $query, User $user): Builder
    {
        return $query
            ->whereNull('additional_user_id')
            ->where('archived_additional_user_id', $user->id)
            ->whereNotNull('attached_archived_at');
    }

    public function scopeReturnedArchiveForUser(Builder $query, User $user): Builder
    {
        return $query
            ->whereNull('additional_user_id')
            ->where('returned_additional_user_id', $user->id);
    }

    public function scopeReturnedToPrimaryUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('assigned_user_id', $user->id)
            ->whereNull('additional_user_id')
            ->whereNotNull('returned_additional_user_id')
            ->whereNotNull('returned_to_primary_at')
            ->whereNull('returned_to_primary_archived_at')
            ->whereNull('approved_returned_at');
    }

    public function scopeApprovedReturned(Builder $query): Builder
    {
        return $query
            ->whereNull('additional_user_id')
            ->whereNotNull('returned_additional_user_id')
            ->whereNotNull('returned_to_primary_at')
            ->whereNotNull('approved_returned_at');
    }

    public function scopeApprovedReturnedForUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('assigned_user_id', $user->id)
            ->approvedReturned();
    }

    public function scopeReturnedToPrimaryArchiveForUser(Builder $query, User $user): Builder
    {
        return $query
            ->whereNull('additional_user_id')
            ->where('returned_to_primary_archived_user_id', $user->id)
            ->whereNotNull('returned_to_primary_archived_at')
            ->whereNull('approved_returned_at');
    }

    public function isMarkedForLater(): bool
    {
        return $this->marked_for_later_at !== null;
    }

    protected function setUpRichContent(): void
    {
        $this->registerRichContent('internal_notes')
            ->fileAttachmentsDisk('local')
            ->fileAttachmentsVisibility('private');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function additionalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'additional_user_id');
    }

    public function returnedAdditionalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_additional_user_id');
    }

    public function returnedToPrimaryArchivedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to_primary_archived_user_id');
    }

    public function approvedReturnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_returned_by_user_id');
    }

    public function archivedAdditionalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_additional_user_id');
    }

    public function guarantors(): HasMany
    {
        return $this->hasMany(LeadGuarantor::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LeadMessage::class);
    }

    public function contractBatches(): HasMany
    {
        return $this->hasMany(ContractBatch::class);
    }
}
