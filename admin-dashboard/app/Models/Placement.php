<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Placement extends Model
{
    protected $table = 'placements';

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'width',
        'height',
        'is_active',
        'adrotate_group_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
