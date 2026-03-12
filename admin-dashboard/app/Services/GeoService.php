<?php

namespace App\Services;

use App\Models\Advertiser;
use App\Models\GeoRegion;

class GeoService
{
    /**
     * Resolve a country_code to an AdRotate geo_countries PHP-serialized string.
     * Mirrors sync-service/src/geo.py resolve_geo_countries().
     */
    public static function resolveGeoCountries(?string $countryCode): string
    {
        if (!$countryCode) {
            return 'a:0:{}';
        }

        $countryCode = strtoupper(trim($countryCode));
        $regions = self::getRegions();

        foreach ($regions as $region) {
            $codes = array_map(fn ($c) => strtoupper(trim($c)), explode(',', $region->country_codes));
            if (in_array($countryCode, $codes, true)) {
                return $region->adrotate_value;
            }
        }

        return 'a:0:{}';
    }

    /**
     * Get the region name for a country_code (for display).
     */
    public static function getRegionName(?string $countryCode): ?string
    {
        if (!$countryCode) {
            return null;
        }

        $countryCode = strtoupper(trim($countryCode));
        $regions = self::getRegions();

        foreach ($regions as $region) {
            $codes = array_map(fn ($c) => strtoupper(trim($c)), explode(',', $region->country_codes));
            if (in_array($countryCode, $codes, true)) {
                return $region->name;
            }
        }

        return null;
    }

    /**
     * Get the matching geo_region row for a country_code (id + name).
     */
    public static function getRegionForCountryCode(?string $countryCode): ?object
    {
        if (!$countryCode) {
            return null;
        }

        $countryCode = strtoupper(trim($countryCode));
        $regions = self::getRegions();

        foreach ($regions as $region) {
            $codes = array_map(fn ($c) => strtoupper(trim($c)), explode(',', $region->country_codes));
            if (in_array($countryCode, $codes, true)) {
                return (object) ['id' => $region->id, 'name' => $region->name];
            }
        }

        return null;
    }

    /**
     * Re-resolve geo_countries for all ads belonging to an advertiser.
     * Called after a country_code override.
     */
    public static function reResolveAdvertiserAds(Advertiser $advertiser): int
    {
        $geoCountries = self::resolveGeoCountries($advertiser->country_code);

        return $advertiser->ads()->update(['geo_countries' => $geoCountries]);
    }

    /**
     * Get all geo regions ordered by priority (cached per request).
     */
    private static function getRegions()
    {
        static $regions = null;

        if ($regions === null) {
            $regions = GeoRegion::orderBy('priority')->get();
        }

        return $regions;
    }
}
