<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const LEAD_PRIMARY_ASSIGNMENT_EMAILS = [
        'anna@creditzona.test',
        'anna@creditzona.bg',
        'elena@creditzona.test',
        'elena@creditzona.bg',
        'krasimira@creditzona.test',
        'krasimira@creditzona.bg',
    ];

    public const LEAD_ADDITIONAL_ASSIGNMENT_EMAILS = [
        ...self::LEAD_PRIMARY_ASSIGNMENT_EMAILS,
        'iskra@creditzona.test',
        'iskra@creditzona.bg',
        'renata@creditzona.test',
        'renata@creditzona.bg',
    ];

    public const ROLE_ADMIN = 'admin';

    public const ROLE_OPERATOR = 'operator';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_available_for_lead_assignment',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_available_for_lead_assignment' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_user_id');
    }

    public function additionalLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'additional_user_id');
    }

    public function uploadedAdminDocuments(): HasMany
    {
        return $this->hasMany(AdminDocument::class, 'uploaded_by_user_id');
    }

    public function createdContractBatches(): HasMany
    {
        return $this->hasMany(ContractBatch::class, 'created_by_user_id');
    }

    public function leadMessages(): HasMany
    {
        return $this->hasMany(LeadMessage::class);
    }

    public function assignedContactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'assigned_user_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function createdCalendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'created_by_user_id');
    }

    public function updatedCalendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'updated_by_user_id');
    }

    public function scopeEligibleForLeadPrimaryAssignment(Builder $query): Builder
    {
        return $query
            ->inLeadPrimaryAssignmentPool()
            ->where('is_available_for_lead_assignment', true);
    }

    public function scopeInLeadPrimaryAssignmentPool(Builder $query): Builder
    {
        return $query
            ->where('role', self::ROLE_OPERATOR)
            ->whereIn('email', self::LEAD_PRIMARY_ASSIGNMENT_EMAILS);
    }

    public function scopeEligibleForLeadAdditionalAssignment(Builder $query): Builder
    {
        return $query
            ->whereIn('role', [self::ROLE_ADMIN, self::ROLE_OPERATOR])
            ->whereIn('email', self::LEAD_ADDITIONAL_ASSIGNMENT_EMAILS);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function canBeLeadPrimaryAssignee(): bool
    {
        return in_array($this->email, self::LEAD_PRIMARY_ASSIGNMENT_EMAILS, strict: true);
    }

    public function canBeLeadAdditionalAssignee(): bool
    {
        return in_array($this->email, self::LEAD_ADDITIONAL_ASSIGNMENT_EMAILS, strict: true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && ($this->isAdmin() || $this->isOperator());
    }

    public function isAvailableForLeadAssignment(): bool
    {
        return (bool) $this->is_available_for_lead_assignment;
    }

    public function canToggleLeadAssignmentAvailability(): bool
    {
        return $this->canBeLeadPrimaryAssignee();
    }
}
