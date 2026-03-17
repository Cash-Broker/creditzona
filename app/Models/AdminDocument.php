<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminDocument extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'original_file_name',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getDisplayTitle(): string
    {
        return $this->title ?: ($this->original_file_name ?: "Файл #{$this->getKey()}");
    }

    public function getFileExtension(): ?string
    {
        $extension = pathinfo($this->original_file_name ?: $this->file_path, PATHINFO_EXTENSION);

        return filled($extension) ? Str::upper($extension) : null;
    }

    public function getReadableFileSize(): ?string
    {
        if (! is_int($this->file_size) || ($this->file_size < 0)) {
            return null;
        }

        if ($this->file_size === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($this->file_size, 1024)), count($units) - 1);
        $value = $this->file_size / (1024 ** $power);
        $precision = $power === 0 ? 0 : 1;

        return number_format($value, $precision, '.', '').' '.$units[$power];
    }

    public function isPreviewableImage(): bool
    {
        if (filled($this->mime_type) && Str::startsWith($this->mime_type, 'image/')) {
            return true;
        }

        return in_array(Str::lower((string) $this->getFileExtension()), [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
            'svg',
        ], strict: true);
    }

    public function fileExists(): bool
    {
        if (blank($this->file_path)) {
            return false;
        }

        return Storage::disk('local')->exists($this->file_path);
    }

    public function syncStoredFileMetadata(): void
    {
        if (! $this->fileExists()) {
            return;
        }

        $disk = Storage::disk('local');
        $updates = [];

        $mimeType = $disk->mimeType($this->file_path);
        $fileSize = $disk->size($this->file_path);

        if (filled($mimeType) && ($mimeType !== $this->mime_type)) {
            $updates['mime_type'] = $mimeType;
        }

        if (is_int($fileSize) && ($fileSize !== $this->file_size)) {
            $updates['file_size'] = $fileSize;
        }

        if ($updates === []) {
            return;
        }

        $this->forceFill($updates)->saveQuietly();
    }
}
