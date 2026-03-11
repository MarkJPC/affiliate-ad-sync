<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'sync_logs';

    public $timestamps = false;

    protected $fillable = [
        'network',
        'site_domain',
        'started_at',
        'completed_at',
        'status',
        'advertisers_synced',
        'ads_synced',
        'ads_deleted',
        'error_message',
    ];

    protected $casts = [
        'advertisers_synced' => 'integer',
        'ads_synced'         => 'integer',
        'ads_deleted'        => 'integer',
        'started_at'         => 'datetime',
        'completed_at'       => 'datetime',
    ];

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('started_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $query->where('started_at', '<=', $to . ' 23:59:59');
        }
        return $query;
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }
        $seconds = $this->completed_at->diffInSeconds($this->started_at);
        if ($seconds < 60) {
            return $seconds . 's';
        }
        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    }
}
