<?php

namespace App\Libraries;

class SuitabilityClassEvaluator
{
    private const CLASS_ORDER = ['S1', 'S2', 'S3', 'N'];

    /**
     * @param array<int, array<string, mixed>> $commodities
     * @param array<int, array<string, mixed>> $criteriaRows
     * @return array<int, array<string, mixed>>
     */
    public function evaluateAll(array $testingData, array $commodities, array $criteriaRows): array
    {
        $criteriaByCommodity = [];
        foreach ($criteriaRows as $row) {
            $criteriaByCommodity[(int) $row['komoditas_id']][] = $row;
        }

        $results = [];

        foreach ($commodities as $commodity) {
            $commodityId = (int) $commodity['id'];
            $rules = $criteriaByCommodity[$commodityId] ?? [];

            $results[$commodityId] = [
                'komoditas_id' => $commodityId,
                'nama'         => (string) $commodity['nama'],
                'warna_peta'   => (string) $commodity['warna_peta'],
                'kelas'        => $this->evaluateCommodity($testingData, $rules),
            ];
        }

        return $results;
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     */
    private function evaluateCommodity(array $testingData, array $rules): string
    {
        if ($rules === []) {
            return 'N';
        }

        $rulesByClass = [];
        foreach ($rules as $rule) {
            $rulesByClass[(string) $rule['kelas']][] = $rule;
        }

        foreach (self::CLASS_ORDER as $class) {
            foreach ($rulesByClass[$class] ?? [] as $rule) {
                if ($this->matchesRule($testingData, $rule)) {
                    return $class;
                }
            }
        }

        return 'N';
    }

    private function matchesRule(array $testingData, array $rule): bool
    {
        $numericPairs = [
            ['kemiringan_lereng', 'kemiringan_min', 'kemiringan_max'],
            ['ph_tanah', 'ph_min', 'ph_max'],
            ['ketinggian_tempat', 'ketinggian_min', 'ketinggian_max'],
            ['kedalaman_tanah', 'kedalaman_min', 'kedalaman_max'],
            ['curah_hujan', 'curah_hujan_min', 'curah_hujan_max'],
            ['irigasi', 'irigasi_min', 'irigasi_max'],
        ];

        foreach ($numericPairs as [$field, $minField, $maxField]) {
            $value = (float) $testingData[$field];

            if ($rule[$minField] !== null && $value < (float) $rule[$minField]) {
                return false;
            }

            if ($rule[$maxField] !== null && $value > (float) $rule[$maxField]) {
                return false;
            }
        }

        $allowedSoils = json_decode((string) ($rule['jenis_tanah_json'] ?? '[]'), true);
        if (is_array($allowedSoils) && $allowedSoils !== []) {
            $currentSoil = strtolower(trim((string) $testingData['jenis_tanah']));
            $normalizedAllowed = array_map(
                static fn(mixed $soil): string => strtolower(trim((string) $soil)),
                $allowedSoils,
            );

            if (! in_array($currentSoil, $normalizedAllowed, true)) {
                return false;
            }
        }

        return true;
    }
}
