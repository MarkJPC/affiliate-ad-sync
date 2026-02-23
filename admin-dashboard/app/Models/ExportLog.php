<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    protected $table = 'export_logs';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'filename',
        'ads_exported',
        'exported_at',
        'exported_by',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
