<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    protected $table = 'ads';

    protected $fillable = [
        'network',
        'network_ad_id',
        'advertiser_id',
        'creative_type',
        'tracking_url',
        'destination_url',
        'html_snippet',
        'status',
        'clicks',
        'revenue',
        'epc',
        'approval_status',
        'approval_reason',
        'weight_override',
        'last_synced_at',
        'raw_hash',
        'advert_name',
        'bannercode',
        'imagetype',
        'image_url',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'epc' => 'float',
            'revenue' => 'float',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }
}
