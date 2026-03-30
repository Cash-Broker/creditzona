<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'message',
        'assigned_user_id',
        'generated_lead_id',
        'lead_generated_at',
        'archived_by_user_id',
        'archived_at',
        'admin_archived_by_user_id',
        'admin_archived_at',
    ];

    protected $casts = [
        'lead_generated_at' => 'datetime',
        'archived_at' => 'datetime',
        'admin_archived_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeAdminActive(Builder $query): Builder
    {
        return $query->whereNull('admin_archived_at');
    }

    public function scopeAdminArchived(Builder $query): Builder
    {
        return $query->whereNotNull('admin_archived_at');
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query->adminActive();
        }

        return $query->active()->where('assigned_user_id', $user->id);
    }

    public function scopeAttachedToUser(Builder $query, User $user): Builder
    {
        return $query->active()->where('assigned_user_id', $user->id);
    }

    public function scopeArchivedForUser(Builder $query, User $user): Builder
    {
        return $query->archived()->where('assigned_user_id', $user->id);
    }

    public function archivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    public function adminArchivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_archived_by_user_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function generatedLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'generated_lead_id');
    }
}
