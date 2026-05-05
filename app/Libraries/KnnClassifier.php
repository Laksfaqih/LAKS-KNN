<?php

namespace App\Libraries;

class KnnClassifier
{
    private const NUMERIC_FIELDS = [
        'kemiringan_lereng',
        'luas_lahan',
        'ph_tanah',
        'ketinggian_tempat',
        'kedalaman_tanah',
        'curah_hujan',
        'irigasi',
        'latitude',
        'longitude',
    ];

    /**
     * @param array<int, array<string, mixed>> $trainingData
     * @param array<int, array<string, mixed>> $commodities
     */
    public function classify(array $testingData, array $trainingData, array $commodities, int $requestedK): array
    {
        if ($trainingData === []) {
            throw new \RuntimeException('Data latih belum tersedia.');
        }

        if ($commodities === []) {
            throw new \RuntimeException('Komoditas aktif belum tersedia.');
        }

        $commodityMap = [];
        foreach ($commodities as $commodity) {
            $commodityMap[(int) $commodity['id']] = $commodity;
        }

        $ranges = $this->buildRanges($trainingData);
        $normalizedTest = $this->normalizeRow($testingData, $ranges);
        $distanceRows = $this->buildDistanceRows($trainingData, $testingData, $normalizedTest, $ranges);

        usort($distanceRows, static fn(array $left, array $right): int => $left['distance'] <=> $right['distance']);

        $effectiveK = min($requestedK, count($distanceRows));
        $neighbors = array_slice($distanceRows, 0, $effectiveK);
        $votes = $this->aggregateVotes($neighbors, $commodityMap);

        if ($votes === []) {
            throw new \RuntimeException('Data latih aktif tidak memiliki jenis tanaman yang valid.');
        }

        usort($votes, static function (array $left, array $right): int {
            $byVotes = $right['votes'] <=> $left['votes'];
            if ($byVotes !== 0) {
                return $byVotes;
            }

            $byDistance = $left['average_distance'] <=> $right['average_distance'];
            if ($byDistance !== 0) {
                return $byDistance;
            }

            return $left['urutan_tampil'] <=> $right['urutan_tampil'];
        });

        return [
            'ranges'          => $ranges,
            'normalized_test' => $normalizedTest,
            'distances'       => $distanceRows,
            'neighbors'       => $neighbors,
            'votes'           => $votes,
            'recommendation'  => $votes[0],
            'effective_k'     => $effectiveK,
            'requested_k'     => $requestedK,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $trainingData
     * @return array<string, array{min: float, max: float}>
     */
    private function buildRanges(array $trainingData): array
    {
        $ranges = [];

        foreach (self::NUMERIC_FIELDS as $field) {
            $values = array_map(static fn(array $row): float => (float) $row[$field], $trainingData);
            $ranges[$field] = [
                'min' => min($values),
                'max' => max($values),
            ];
        }

        return $ranges;
    }

    /**
     * @param array<string, array{min: float, max: float}> $ranges
     * @return array<string, float>
     */
    private function normalizeRow(array $row, array $ranges): array
    {
        $normalized = [];

        foreach (self::NUMERIC_FIELDS as $field) {
            $value = (float) $row[$field];
            $min = (float) $ranges[$field]['min'];
            $max = (float) $ranges[$field]['max'];
            $normalized[$field] = $max === $min ? 0.0 : ($value - $min) / ($max - $min);
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $trainingData
     * @param array<string, float> $normalizedTest
     * @param array<string, array{min: float, max: float}> $ranges
     * @return array<int, array<string, mixed>>
     */
    private function buildDistanceRows(array $trainingData, array $testingData, array $normalizedTest, array $ranges): array
    {
        $rows = [];

        foreach ($trainingData as $row) {
            $normalizedTraining = $this->normalizeRow($row, $ranges);
            $sum = 0.0;

            foreach (self::NUMERIC_FIELDS as $field) {
                $delta = $normalizedTest[$field] - $normalizedTraining[$field];
                $sum += $delta ** 2;
            }

            $jenisTanahDelta = strtolower((string) $testingData['jenis_tanah']) === strtolower((string) $row['jenis_tanah']) ? 0.0 : 1.0;
            $sum += $jenisTanahDelta ** 2;

            $rows[] = [
                'training_id'         => (int) $row['id'],
                'komoditas_id'        => (int) $row['komoditas_id'],
                'distance'            => sqrt($sum),
                'normalized_training' => $normalizedTraining,
                'jenis_tanah'         => $row['jenis_tanah'],
                'raw'                 => $row,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $neighbors
     * @param array<int, array<string, mixed>> $commodityMap
     * @return array<int, array<string, mixed>>
     */
    private function aggregateVotes(array $neighbors, array $commodityMap): array
    {
        $groups = [];

        foreach ($neighbors as $neighbor) {
            $commodityId = (int) ($neighbor['komoditas_id'] ?? 0);
            if ($commodityId === 0 || ! isset($commodityMap[$commodityId])) {
                continue;
            }

            if (! isset($groups[$commodityId])) {
                $commodity = $commodityMap[$commodityId];
                $groups[$commodityId] = [
                    'komoditas_id'     => $commodityId,
                    'nama'             => (string) $commodity['nama'],
                    'warna_peta'       => (string) $commodity['warna_peta'],
                    'urutan_tampil'    => (int) ($commodity['urutan_tampil'] ?? 0),
                    'votes'            => 0,
                    'distance_total'   => 0.0,
                    'average_distance' => INF,
                ];
            }

            $groups[$commodityId]['votes']++;
            $groups[$commodityId]['distance_total'] += (float) $neighbor['distance'];
        }

        foreach ($groups as &$group) {
            $group['average_distance'] = $group['votes'] > 0
                ? $group['distance_total'] / $group['votes']
                : INF;
            unset($group['distance_total']);
        }

        return array_values($groups);
    }
}
