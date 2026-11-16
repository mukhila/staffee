<?php

namespace App\Services\Shift;

use App\Models\Setting;
use InvalidArgumentException;

/**
 * Optional office geofence for clock-in and clock-out.
 *
 * Configured from Settings (geofence_enabled, geofence_latitude,
 * geofence_longitude, geofence_radius_m). When it is off, no location is
 * requested or stored.
 */
class GeofenceService
{
    public const DEFAULT_RADIUS_M = 200;

    private const EARTH_RADIUS_M = 6371000;

    private ?array $config = null;

    /** @return array{enabled: bool, latitude: ?float, longitude: ?float, radius_m: int} */
    public function config(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $settings = Setting::whereIn('key', [
            'geofence_enabled', 'geofence_latitude', 'geofence_longitude', 'geofence_radius_m',
        ])->pluck('value', 'key');

        $latitude  = is_numeric($settings['geofence_latitude'] ?? null) ? (float) $settings['geofence_latitude'] : null;
        $longitude = is_numeric($settings['geofence_longitude'] ?? null) ? (float) $settings['geofence_longitude'] : null;
        $radius    = (int) ($settings['geofence_radius_m'] ?? 0);

        return $this->config = [
            // Switched on but without an office location, it cannot be enforced.
            'enabled'   => ($settings['geofence_enabled'] ?? '0') === '1' && $latitude !== null && $longitude !== null,
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'radius_m'  => $radius > 0 ? $radius : self::DEFAULT_RADIUS_M,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->config()['enabled'];
    }

    /**
     * Check a reported position against the office geofence.
     *
     * @return int distance from the office in metres
     *
     * @throws InvalidArgumentException when the position is missing, invalid or outside the radius
     */
    public function assertInside(mixed $latitude, mixed $longitude): int
    {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new InvalidArgumentException(
                'Your location is required. Allow location access in your browser and try again.'
            );
        }

        $latitude  = (float) $latitude;
        $longitude = (float) $longitude;

        if (abs($latitude) > 90 || abs($longitude) > 180) {
            throw new InvalidArgumentException('The location sent by your browser is not valid.');
        }

        $config   = $this->config();
        $distance = $this->distanceMeters($latitude, $longitude, $config['latitude'], $config['longitude']);

        if ($distance > $config['radius_m']) {
            throw new InvalidArgumentException(
                "You are {$distance} m from the office. You must be within {$config['radius_m']} m."
            );
        }

        return $distance;
    }

    /** Great-circle (haversine) distance in whole metres. */
    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round(self::EARTH_RADIUS_M * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
