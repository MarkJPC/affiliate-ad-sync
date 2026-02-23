<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Advertiser extends Model
{
    protected $table = 'advertisers';

    protected $fillable = [
        'network',
        'network_advertiser_id',
        'name',
        'website_url',
        'category',
        'total_clicks',
        'total_revenue',
        'epc',
        'commission_rate',
        'default_weight',
        'is_active',
        'last_synced_at',
        'raw_hash',
    ];

    protected function casts(): array
    {
        return [
            'epc' => 'float',
            'total_revenue' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function siteRules(): HasMany
    {
        return $this->hasMany(SiteAdvertiserRule::class);
    }
}
