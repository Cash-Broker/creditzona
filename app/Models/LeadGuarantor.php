<?php

namespace App\Models;

use App\Support\Phone\PhoneNormalizer;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LeadGuarantor extends Model implements HasRichContent
{
    use InteractsWithRichContent;

    public const STATUS_SUITABLE = 'suitable';

    public const STATUS_UNSUITABLE = 'unsuitable';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'lead_id',
        'first_name',
        'middle_name',
        'last_name',
        'egn',
        'phone',
        'email',
        'city',
        'workplace',
        'job_title',
        'salary',
        'marital_status',
        'marital_status_note',
        'children_under_18',
        'salary_bank',
        'credit_bank',
        'amount',
        'property_type',
        'property_location',
        'documents',
        'document_file_names',
        'internal_notes',
        'status',
        'privacy_consent_accepted_at',
    ];

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_SUITABLE => 'Годен',
            self::STATUS_UNSUITABLE => 'Негоден',
            self::STATUS_DECLINED => 'Отказал се',
        ];
    }

    public static function getStatusLabel(?string $state): string
    {
        return static::getStatusOptions()[$state] ?? ($state ?: 'Няма');
    }

    public static function getVisualStateKey(?string $status): string
    {
        return match ($status) {
            self::STATUS_SUITABLE => 'suitable',
            self::STATUS_UNSUITABLE => 'unsuitable',
            self::STATUS_DECLINED => 'declined',
            default => 'attention',
        };
    }

    public static function getSurfaceClasses(?string $status): string
    {
        return 'lead-guarantor-surface lead-guarantor-surface--'.static::getVisualStateKey($status);
    }

    public static function getItemLabelClasses(?string $status): string
    {
        return 'lead-guarantor-item-label lead-guarantor-item-label--'.static::getVisualStateKey($status);
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

    protected function casts(): array
    {
        return [
            'egn' => 'encrypted',
            'salary' => 'integer',
            'children_under_18' => 'integer',
            'amount' => 'integer',
            'documents' => 'array',
            'document_file_names' => 'array',
            'privacy_consent_accepted_at' => 'datetime',
        ];
    }

    public function setPhoneAttribute(mixed $value): void
    {
        $this->attributes['phone'] = PhoneNormalizer::normalize($value);
    }

    protected function setUpRichContent(): void
    {
        $this->registerRichContent('internal_notes')
            ->fileAttachmentsDisk('local')
            ->fileAttachmentsVisibility('private');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    private function sanitizePrivacyConsentDownloadSegment(?string $value): ?string
    {
        $sanitized = preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $value);

        return filled($sanitized) ? $sanitized : null;
    }
}
