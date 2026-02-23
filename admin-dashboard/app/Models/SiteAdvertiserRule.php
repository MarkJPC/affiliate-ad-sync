<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteAdvertiserRule extends Model
{
    protected $table = 'site_advertiser_rules';

    protected $fillable = [
        'site_id',
        'advertiser_id',
        'rule',
        'reason',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }
}
