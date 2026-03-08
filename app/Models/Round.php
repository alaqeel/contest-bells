<?php

namespace App\Models;

use App\Enums\RoundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    protected $fillable = [
        'competition_id',
        'round_number',
        'status',
        'first_buzz_contestant_id',
        'buzz_opened_at',
        'first_buzzed_at',
        'answer_deadline_at',
        'resolved_at',
    ];

    protected $casts = [
        'status'            => RoundStatus::class,
        'buzz_opened_at'    => 'datetime',
        'first_buzzed_at'   => 'datetime',
        'answer_deadline_at'=> 'datetime',
        'resolved_at'       => 'datetime',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function firstBuzzContestant(): BelongsTo
    {
        return $this->belongsTo(Contestant::class, 'first_buzz_contestant_id');
    }

    public function buzzAttempts(): HasMany
    {
        return $this->hasMany(BuzzAttempt::class);
    }

    public function lockouts(): HasMany
    {
        return $this->hasMany(ContestantLockout::class);
    }

    public function isActive(): bool
    {
        return $this->status === RoundStatus::Active;
    }

    public function isLocked(): bool
    {
        return $this->status === RoundStatus::Locked;
    }

    public function isCompleted(): bool
    {
        return $this->status === RoundStatus::Completed;
    }
}
