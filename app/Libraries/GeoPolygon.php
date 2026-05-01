<?php

namespace App\Libraries;

class GeoPolygon
{
    public function centroidFromGeoJson(string $geoJson): array
    {
        $decoded = json_decode($geoJson, true);
        $coordinates = $decoded['geometry']['coordinates'][0] ?? null;

        if (! is_array($coordinates) || $coordinates === []) {
            throw new \RuntimeException('Polygon GeoJSON tidak valid.');
        }

        $lngSum = 0.0;
        $latSum = 0.0;
        $count = 0;

        foreach ($coordinates as $point) {
            if (! is_array($point) || count($point) < 2) {
                continue;
            }

            $lngSum += (float) $point[0];
            $latSum += (float) $point[1];
            $count++;
        }

        if ($count === 0) {
            throw new \RuntimeException('Polygon GeoJSON tidak memiliki koordinat yang valid.');
        }

        return [
            'longitude' => $lngSum / $count,
            'latitude'  => $latSum / $count,
        ];
    }
}
