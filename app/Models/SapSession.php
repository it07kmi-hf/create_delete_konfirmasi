<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class SapSession extends Model
{
    protected $table = 'sap_sessions';

    protected $fillable = [
        'laravel_session_id',
        'sap_username',
        'sap_password_encrypted',
        'user_display',
        'logged_in_at',
        'last_validated_at',
        'expires_at',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'last_validated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope untuk session yang masih aktif (belum expired)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope untuk session yang sudah expired
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check jika session sudah expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check jika session masih valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Get minutes until expiration
     */
    public function minutesUntilExpiration(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInMinutes($this->expires_at, false);
    }

    /**
     * Get minutes since last validation
     */
    public function minutesSinceLastValidation(): ?int
    {
        if (!$this->last_validated_at) {
            return null;
        }

        return $this->last_validated_at->diffInMinutes(now());
    }
}