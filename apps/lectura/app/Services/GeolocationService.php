<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Http\Request;

class GeolocationService
{
    /**
     * Earth's radius in meters.
     */
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Calculate distance between two coordinates using Haversine formula.
     *
     * @param  float  $lat1  First latitude
     * @param  float  $lon1  First longitude
     * @param  float  $lat2  Second latitude
     * @param  float  $lon2  Second longitude
     * @return float Distance in meters
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Check if user location is within campus radius.
     *
     * @param  float  $userLat  User's latitude
     * @param  float  $userLon  User's longitude
     * @param  Campus  $campus  Campus model
     * @return array{within_radius: bool, distance: float}
     */
    public function isWithinCampusRadius(float $userLat, float $userLon, Campus $campus): array
    {
        $distance = $this->calculateDistance(
            $userLat,
            $userLon,
            (float) $campus->latitude,
            (float) $campus->longitude
        );

        return [
            'within_radius' => $distance <= $campus->radius_meters,
            'distance' => round($distance, 2),
        ];
    }

    /**
     * Get location data from request.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function getLocationFromRequest(Request $request): ?array
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        // Validate coordinate ranges
        if ($latitude < -90 || $latitude > 90) {
            return null;
        }

        if ($longitude < -180 || $longitude > 180) {
            return null;
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * Format distance for human display.
     *
     * @param  float  $meters  Distance in meters
     * @return string Formatted distance
     */
    public function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters).' m';
        }

        return number_format($meters / 1000, 2).' km';
    }
}
