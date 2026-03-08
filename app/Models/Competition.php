<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Competition extends Model
{
    protected $fillable = [
        'room_code',
        'judge_token',
        'title',
        'status',
        'contestant_count',
        'current_round_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'status'       => CompetitionStatus::class,
        'started_at'   => 'datetime',
        'ended_at'     => 'datetime',
    ];

    public function contestants(): HasMany
    {
        return $this->hasMany(Contestant::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('round_number');
    }

    public function currentRound(): BelongsTo
    {
        return $this->belongsTo(Round::class, 'current_round_id');
    }

    public function buzzAttempts(): HasManyThrough
    {
        return $this->hasManyThrough(BuzzAttempt::class, Round::class);
    }

    public function isActive(): bool
    {
        return $this->status === CompetitionStatus::Active;
    }

    public function isEnded(): bool
    {
        return $this->status === CompetitionStatus::Ended;
    }
}
