<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
        'phone',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function ticketReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function permissions(): Collection
    {
        if (! $this->relationLoaded('role')) {
            $this->load('role.permissions');
        }

        return $this->role?->permissions ?? collect();
    }

    public function hasPermission(string|array $slugs): bool
    {
        $slugs = (array) $slugs;
        $permissionSlugs = $this->permissions()->pluck('slug')->toArray();

        return count(array_intersect($slugs, $permissionSlugs)) === count($slugs);
    }

    public function hasAnyPermission(string|array $slugs): bool
    {
        $slugs = (array) $slugs;
        $permissionSlugs = $this->permissions()->pluck('slug')->toArray();

        return count(array_intersect($slugs, $permissionSlugs)) > 0;
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        $userRole = $this->role?->slug;

        return $userRole && in_array($userRole, $roles);
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === RoleEnum::ADMIN->value;
    }

    public function isAgent(): bool
    {
        return $this->role?->slug === RoleEnum::AGENT->value;
    }

    public function isCustomer(): bool
    {
        return $this->role?->slug === RoleEnum::CUSTOMER->value;
    }

    public function isActive(): bool
    {
        return $this->is_active ?? false;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
