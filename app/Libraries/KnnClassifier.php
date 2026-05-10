<?php

namespace App\Libraries;

class KnnClassifier
{
    private const NUMERIC_FIELDS = [
        'kemiringan_lereng',
        'ketinggian_tempat',
        'curah_hujan',
        'ph_tanah',
        'kedalaman_tanah',
        'irigasi',
    ];
    private const SOIL_CODE_MAP = [
        'tanah lempung' => 1.0,
        'tanah kubi' => 2.0,
        'tanah liat' => 3.0,
        'tanah berpasir' => 4.0,
    ];

    /**
     * @param array<int, array<string, mixed>> $trainingData
     * @return array<string, mixed>
     */
    public function classify(array $testingData, array $trainingData, int $requestedK): array
    {
        if ($trainingData === []) {
            throw new \RuntimeException('Data latih untuk tanaman yang dipilih belum tersedia.');
        }

        $distanceRows = $this->buildDistanceRows($trainingData, $testingData);

        usort($distanceRows, static function (array $left, array $right): int {
            $byDistance = $left['distance'] <=> $right['distance'];
            if ($byDistance !== 0) {
                return $byDistance;
            }

            return $left['training_id'] <=> $right['training_id'];
        });

        $effectiveK = min($requestedK, count($distanceRows));
        $neighbors = array_slice($distanceRows, 0, $effectiveK);
        $votes = $this->aggregateVotes($neighbors);

        if ($votes === []) {
            throw new \RuntimeException('Data latih aktif tidak memiliki label kelas yang valid.');
        }

        usort($votes, static function (array $left, array $right): int {
            $byVotes = $right['votes'] <=> $left['votes'];
            if ($byVotes !== 0) {
                return $byVotes;
            }

            return $left['average_distance'] <=> $right['average_distance'];
        });

        return [
            'distances'       => $distanceRows,
            'neighbors'       => $neighbors,
            'votes'           => $votes,
            'prediction'      => $votes[0],
            'effective_k'     => $effectiveK,
            'requested_k'     => $requestedK,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $trainingData
     * @return array<int, array<string, mixed>>
     */
    private function buildDistanceRows(array $trainingData, array $testingData): array
    {
        $rows = [];
        $testingSoil = $this->soilCode((string) ($testingData['jenis_tanah'] ?? ''));

        foreach ($trainingData as $row) {
            $sum = 0.0;

            foreach (self::NUMERIC_FIELDS as $field) {
                $delta = (float) $testingData[$field] - (float) $row[$field];
                $sum += $delta ** 2;
            }

            $jenisTanahDelta = $testingSoil - $this->soilCode((string) ($row['jenis_tanah'] ?? ''));
            $sum += $jenisTanahDelta ** 2;

            $rows[] = [
                'training_id'  => (int) $row['id'],
                'komoditas_id' => (int) ($row['komoditas_id'] ?? 0),
                'kelas'        => (string) ($row['kelas'] ?? ''),
                'distance'     => sqrt($sum),
                'jenis_tanah'  => $row['jenis_tanah'],
                'raw'          => $row,
            ];
        }

        return $rows;
    }

    private function soilCode(string $value): float
    {
        $normalized = strtolower(trim($value));

        return self::SOIL_CODE_MAP[$normalized] ?? 0.0;
    }

    /**
     * @param array<int, array<string, mixed>> $neighbors
     * @return array<int, array<string, mixed>>
     */
    private function aggregateVotes(array $neighbors): array
    {
        $groups = [];

        foreach ($neighbors as $neighbor) {
            $kelas = strtoupper(trim((string) ($neighbor['kelas'] ?? '')));
            if (! in_array($kelas, ['S1', 'S2', 'S3', 'N'], true)) {
                continue;
            }

            if (! isset($groups[$kelas])) {
                $groups[$kelas] = [
                    'kelas'            => $kelas,
                    'votes'            => 0,
                    'distance_total'   => 0.0,
                    'average_distance' => INF,
                ];
            }

            $groups[$kelas]['votes']++;
            $groups[$kelas]['distance_total'] += (float) $neighbor['distance'];
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
