<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoRegion extends Model
{
    protected $table = 'geo_regions';

    protected $fillable = ['name', 'priority', 'country_codes', 'adrotate_value'];

    public $timestamps = false;

    const CREATED_AT = 'created_at';
}
