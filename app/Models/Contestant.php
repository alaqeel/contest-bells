<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contestant extends Model
{
    protected $fillable = [
        'competition_id',
        'display_name',
        'score',
        'claim_token',
        'session_id',
        'claimed_at',
        'is_connected',
    ];

    protected $casts = [
        'claimed_at'   => 'datetime',
        'is_connected' => 'boolean',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function buzzAttempts(): HasMany
    {
        return $this->hasMany(BuzzAttempt::class);
    }

    public function lockouts(): HasMany
    {
        return $this->hasMany(ContestantLockout::class);
    }

    public function isClaimed(): bool
    {
        return $this->claim_token !== null;
    }

    /** Check if this contestant is locked out in a given round right now. */
    public function isLockedOutInRound(Round $round): bool
    {
        return $this->lockouts()
            ->where('round_id', $round->id)
            ->where('locked_until', '>', now())
            ->exists();
    }
}
