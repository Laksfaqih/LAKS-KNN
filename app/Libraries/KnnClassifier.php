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
    ];

    private const CLASS_PRIORITY = [
        'S1' => 4,
        'S2' => 3,
        'S3' => 2,
        'N'  => 1,
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

        $ranges = $this->buildRanges($trainingData);
        $normalizedTest = $this->normalizeRow($testingData, $ranges);
        $distanceRows = $this->buildDistanceRows($trainingData, $testingData, $normalizedTest, $ranges);

        usort($distanceRows, static fn(array $left, array $right): int => $left['distance'] <=> $right['distance']);

        $effectiveK = min($requestedK, count($distanceRows));
        $neighbors = array_slice($distanceRows, 0, $effectiveK);

        $predictions = [];
        $recommendationCandidates = [];

        foreach ($commodities as $commodity) {
            $commodityId = (int) $commodity['id'];
            $decision = $this->decideClass($neighbors, $commodityId);

            $predictions[$commodityId] = [
                'komoditas_id' => $commodityId,
                'nama'         => (string) $commodity['nama'],
                'slug'         => (string) $commodity['slug'],
                'warna_peta'   => (string) $commodity['warna_peta'],
                'kelas'        => $decision['class'],
                'votes'        => $decision['votes'],
                'average_distance' => $decision['average_distance'],
            ];

            $recommendationCandidates[] = [
                'komoditas_id'      => $commodityId,
                'nama'              => (string) $commodity['nama'],
                'slug'              => (string) $commodity['slug'],
                'warna_peta'        => (string) $commodity['warna_peta'],
                'kelas'             => $decision['class'],
                'class_priority'    => self::CLASS_PRIORITY[$decision['class']] ?? 0,
                'votes'             => $decision['votes'],
                'average_distance'  => $decision['average_distance'],
                'urutan_tampil'     => (int) ($commodity['urutan_tampil'] ?? 0),
            ];
        }

        usort($recommendationCandidates, static function (array $left, array $right): int {
            $byClass = $right['class_priority'] <=> $left['class_priority'];
            if ($byClass !== 0) {
                return $byClass;
            }

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
            'ranges'              => $ranges,
            'normalized_test'     => $normalizedTest,
            'distances'           => $distanceRows,
            'neighbors'           => $neighbors,
            'predictions'         => $predictions,
            'recommendation'      => $recommendationCandidates[0],
            'effective_k'         => $effectiveK,
            'requested_k'         => $requestedK,
        ];
    }

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
                'distance'            => sqrt($sum),
                'normalized_training' => $normalizedTraining,
                'jenis_tanah'         => $row['jenis_tanah'],
                'commodity_labels'    => $row['commodity_labels'] ?? [],
                'raw'                 => $row,
            ];
        }

        return $rows;
    }

    private function decideClass(array $neighbors, int $commodityId): array
    {
        $groups = [];

        foreach ($neighbors as $neighbor) {
            $class = (string) ($neighbor['commodity_labels'][$commodityId] ?? '');
            if ($class === '') {
                continue;
            }

            if (! isset($groups[$class])) {
                $groups[$class] = [
                    'class'          => $class,
                    'votes'          => 0,
                    'distance_total' => 0.0,
                ];
            }

            $groups[$class]['votes']++;
            $groups[$class]['distance_total'] += (float) $neighbor['distance'];
        }

        if ($groups === []) {
            return [
                'class'            => 'N',
                'votes'            => 0,
                'average_distance' => INF,
            ];
        }

        $decisions = array_values(array_map(static function (array $item): array {
            $item['average_distance'] = $item['votes'] > 0 ? $item['distance_total'] / $item['votes'] : INF;

            return $item;
        }, $groups));

        usort($decisions, static function (array $left, array $right): int {
            $byVotes = $right['votes'] <=> $left['votes'];
            if ($byVotes !== 0) {
                return $byVotes;
            }

            $byDistance = $left['average_distance'] <=> $right['average_distance'];
            if ($byDistance !== 0) {
                return $byDistance;
            }

            return (self::CLASS_PRIORITY[$right['class']] ?? 0) <=> (self::CLASS_PRIORITY[$left['class']] ?? 0);
        });

        return $decisions[0];
    }
}
