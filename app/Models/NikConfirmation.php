<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class NikConfirmation extends Model
{
    use HasFactory;

    protected $table = 'nik_confirmations';

    protected $fillable = [
        'pernr',
        'werks',
        'name1',
        'created_by',
        'created_on',
        'synced_at',
    ];

    protected $casts = [
        'created_on' => 'date',
        'synced_at' => 'datetime',
    ];

    /**
     * Get PERNR without leading zeros for display
     */
    public function getPernrDisplayAttribute(): string
    {
        return ltrim($this->pernr, '0');
    }

    /**
     * Get formatted created_on date
     */
    public function getCreatedOnFormattedAttribute(): ?string
    {
        return $this->created_on ? $this->created_on->format('d.m.Y') : null;
    }

    /**
     * Get formatted synced_at timestamp
     */
    public function getSyncedAtFormattedAttribute(): ?string
    {
        return $this->synced_at ? $this->synced_at->format('d.m.Y H:i:s') : null;
    }

    /**
     * Scope: Filter by PERNR
     */
    public function scopeByPernr($query, ?string $pernr)
    {
        if (empty($pernr)) {
            return $query;
        }
        
        // Format PERNR to 8 digits with leading zeros
        $formatted = str_pad(trim($pernr), 8, '0', STR_PAD_LEFT);
        return $query->where('pernr', $formatted);
    }

    /**
     * Scope: Filter by WERKS
     */
    public function scopeByWerks($query, ?string $werks)
    {
        if (empty($werks)) {
            return $query;
        }
        
        return $query->where('werks', 'LIKE', trim($werks) . '%');
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearchName($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }
        
        return $query->where('name1', 'LIKE', '%' . $search . '%');
    }

    /**
     * Scope: Recently synced
     */
    public function scopeRecentlySynced($query, int $hours = 24)
    {
        return $query->where('synced_at', '>=', Carbon::now()->subHours($hours));
    }
}