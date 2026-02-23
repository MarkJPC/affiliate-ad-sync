<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $table = 'sites';

    protected $fillable = [
        'name',
        'domain',
        'wordpress_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }

    public function advertiserRules(): HasMany
    {
        return $this->hasMany(SiteAdvertiserRule::class);
    }

    public function exportLogs(): HasMany
    {
        return $this->hasMany(ExportLog::class);
    }
}
