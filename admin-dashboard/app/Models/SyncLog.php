<?php

namespace App\Models;

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
}
