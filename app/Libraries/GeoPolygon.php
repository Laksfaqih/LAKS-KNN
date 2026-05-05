<?php

namespace App\Libraries;

class GeoPolygon
{
    public function squareGeoJsonFromPoint(float $latitude, float $longitude, float $sideMeters = 50.0): string
    {
        $halfSide = $sideMeters / 2.0;
        $metersPerDegreeLat = 111320.0;
        $metersPerDegreeLng = max(111320.0 * cos(deg2rad($latitude)), 0.0001);

        $latOffset = $halfSide / $metersPerDegreeLat;
        $lngOffset = $halfSide / $metersPerDegreeLng;

        $south = $latitude - $latOffset;
        $north = $latitude + $latOffset;
        $west = $longitude - $lngOffset;
        $east = $longitude + $lngOffset;

        return json_encode([
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [$west, $south],
                    [$east, $south],
                    [$east, $north],
                    [$west, $north],
                    [$west, $south],
                ]],
            ],
            'properties' => [
                'generated' => true,
                'source' => 'coordinate_input',
                'side_meters' => $sideMeters,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

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
