<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use HasFactory;

    public const TYPE_APPOINTMENT = 'appointment';

    public const TYPE_CALL = 'call';

    public const TYPE_FOLLOW_UP = 'follow_up';

    public const TYPE_TASK = 'task';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'all_day',
        'event_type',
        'status',
        'color',
        'user_id',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getEventTypeOptions(): array
    {
        return [
            self::TYPE_APPOINTMENT => 'Среща',
            self::TYPE_CALL => 'Обаждане',
            self::TYPE_FOLLOW_UP => 'Последващ контакт',
            self::TYPE_TASK => 'Задача',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Планирано',
            self::STATUS_COMPLETED => 'Приключено',
            self::STATUS_CANCELLED => 'Отказано',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getDefaultTypeColors(): array
    {
        return [
            self::TYPE_APPOINTMENT => '#2563eb',
            self::TYPE_CALL => '#0891b2',
            self::TYPE_FOLLOW_UP => '#d97706',
            self::TYPE_TASK => '#7c3aed',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isOperator()) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    public function getResolvedColor(): string
    {
        return $this->color ?: static::getDefaultTypeColors()[$this->event_type] ?? '#2563eb';
    }

    public function getTypeLabel(): string
    {
        return static::getEventTypeOptions()[$this->event_type] ?? $this->event_type;
    }

    public function getStatusLabel(): string
    {
        return static::getStatusOptions()[$this->status] ?? $this->status;
    }
}
