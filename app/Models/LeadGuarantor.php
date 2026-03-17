<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadGuarantor extends Model
{
    public const STATUS_SUITABLE = 'suitable';

    public const STATUS_UNSUITABLE = 'unsuitable';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'lead_id',
        'first_name',
        'last_name',
        'egn',
        'phone',
        'status',
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

    protected function casts(): array
    {
        return [
            'egn' => 'encrypted',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
