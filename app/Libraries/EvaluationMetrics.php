<?php

namespace App\Libraries;

class EvaluationMetrics
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
        'tanah lempung'  => 1.0,
        'tanah kubi'     => 2.0,
        'tanah liat'     => 3.0,
        'tanah berpasir' => 4.0,
    ];

    /**
     * Compute min-max normalization for test data against training data ranges.
     *
     * @param array<string, mixed> $testData
     * @param array<int, array<string, mixed>> $trainingData
     * @return array{fields: array<int, array{nama: string, min: float, max: float, value: float, normalized: float}>, soil: array{value: string, code: float, min: float, max: float, normalized: float}}
     */
    public function computeNormalization(array $testData, array $trainingData): array
    {
        $mins = [];
        $maxs = [];
        foreach (self::NUMERIC_FIELDS as $field) {
            $values = array_column($trainingData, $field);
            $mins[$field] = min($values);
            $maxs[$field] = max($values);
        }

        $soilValues = array_map(fn(array $row): float => $this->soilCode((string) ($row['jenis_tanah'] ?? '')), $trainingData);
        $soilMin = min($soilValues);
        $soilMax = max($soilValues);

        $fieldLabels = [
            'kemiringan_lereng' => 'Kemiringan Lereng (%)',
            'ketinggian_tempat' => 'Ketinggian (mdpl)',
            'curah_hujan'       => 'Curah Hujan (mm/tahun)',
            'ph_tanah'          => 'pH Tanah',
            'kedalaman_tanah'   => 'Kedalaman Tanah (cm)',
            'irigasi'           => 'Irigasi (m)',
        ];

        $fields = [];
        foreach (self::NUMERIC_FIELDS as $field) {
            $value = (float) $testData[$field];
            $min   = $mins[$field];
            $max   = $maxs[$field];
            $range = $max - $min;
            $normalized = $range > 0 ? round(($value - $min) / $range, 4) : 0.0;

            $fields[] = [
                'nama'       => $fieldLabels[$field] ?? $field,
                'min'        => $min,
                'max'        => $max,
                'value'      => $value,
                'normalized' => $normalized,
            ];
        }

        $testSoilCode = $this->soilCode((string) ($testData['jenis_tanah'] ?? ''));
        $soilRange = $soilMax - $soilMin;
        $fields[] = [
            'nama'       => 'Jenis Tanah (kode)',
            'min'        => $soilMin,
            'max'        => $soilMax,
            'value'      => $testSoilCode,
            'normalized' => $soilRange > 0 ? round(($testSoilCode - $soilMin) / $soilRange, 4) : 0.0,
        ];

        return [
            'fields' => $fields,
        ];
    }

    /**
     * Compute confusion matrix using Leave-One-Out Cross-Validation.
     *
     * @param array<int, array<string, mixed>> $trainingData
     * @param int $k
     * @return array{matrix: array<string, array<string, int>>, labels: array<int, string>, accuracy: float, precision: array<string, float>, recall: array<string, float>, f1: array<string, float>}
     */
    public function computeConfusionMatrix(array $trainingData, int $k): array
    {
        $labels = ['S1', 'S2', 'S3', 'N'];
        $matrix = [];
        foreach ($labels as $actual) {
            $matrix[$actual] = [];
            foreach ($labels as $predicted) {
                $matrix[$actual][$predicted] = 0;
            }
        }

        $n = count($trainingData);
        for ($i = 0; $i < $n; $i++) {
            $testRow = $trainingData[$i];
            $actualClass = (string) ($testRow['kelas'] ?? '');

            $distances = [];
            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) {
                    continue;
                }
                $distances[] = [
                    'distance' => $this->euclideanDistance($testRow, $trainingData[$j]),
                    'kelas'    => (string) ($trainingData[$j]['kelas'] ?? ''),
                ];
            }

            usort($distances, static fn(array $a, array $b): int => $a['distance'] <=> $b['distance']);
            $effectiveK = min($k, count($distances));
            $topK = array_slice($distances, 0, $effectiveK);

            $votes = [];
            foreach ($topK as $neighbor) {
                $kelas = strtoupper(trim($neighbor['kelas']));
                if (! isset($votes[$kelas])) {
                    $votes[$kelas] = 0;
                }
                $votes[$kelas]++;
            }
            arsort($votes);
            $predictedClass = (string) array_key_first($votes);

            if (isset($matrix[$actualClass][$predictedClass])) {
                $matrix[$actualClass][$predictedClass]++;
            }
        }

        $totalCorrect = 0;
        $totalAll = 0;
        foreach ($labels as $label) {
            $totalCorrect += $matrix[$label][$label] ?? 0;
            $totalAll += array_sum($matrix[$label]);
        }
        $accuracy = $totalAll > 0 ? round($totalCorrect / $totalAll * 100, 2) : 0.0;

        $precision = [];
        $recall = [];
        $f1 = [];
        foreach ($labels as $label) {
            $colSum = 0;
            foreach ($labels as $row) {
                $colSum += $matrix[$row][$label] ?? 0;
            }
            $rowSum = array_sum($matrix[$label]);

            $precision[$label] = $colSum > 0 ? round(($matrix[$label][$label] ?? 0) / $colSum, 4) : 0.0;
            $recall[$label]    = $rowSum > 0 ? round(($matrix[$label][$label] ?? 0) / $rowSum, 4) : 0.0;
            $f1[$label]        = ($precision[$label] + $recall[$label]) > 0
                ? round(2 * $precision[$label] * $recall[$label] / ($precision[$label] + $recall[$label]), 4)
                : 0.0;
        }

        return [
            'matrix'    => $matrix,
            'labels'    => $labels,
            'accuracy'  => $accuracy,
            'precision' => $precision,
            'recall'    => $recall,
            'f1'        => $f1,
        ];
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach (self::NUMERIC_FIELDS as $field) {
            $delta = (float) ($a[$field] ?? 0) - (float) ($b[$field] ?? 0);
            $sum += $delta ** 2;
        }
        $soilDelta = $this->soilCode((string) ($a['jenis_tanah'] ?? '')) - $this->soilCode((string) ($b['jenis_tanah'] ?? ''));
        $sum += $soilDelta ** 2;

        return sqrt($sum);
    }

    private function soilCode(string $value): float
    {
        $normalized = strtolower(trim($value));

        return self::SOIL_CODE_MAP[$normalized] ?? 0.0;
    }
}
