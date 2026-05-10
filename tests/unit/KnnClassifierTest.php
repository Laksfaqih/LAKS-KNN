<?php

use App\Libraries\KnnClassifier;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class KnnClassifierTest extends CIUnitTestCase
{
    public function testPredictsClassUsingRawEuclideanDistanceAcrossAllTrainingRows(): void
    {
        $classifier = new KnnClassifier();

        $result = $classifier->classify(
            [
                'kemiringan_lereng' => 5,
                'ketinggian_tempat' => 30,
                'curah_hujan'       => 650,
                'ph_tanah'          => 6.2,
                'jenis_tanah'       => 'tanah lempung',
                'kedalaman_tanah'   => 40,
                'irigasi'           => 100,
            ],
            [
                [
                    'id'                => 10,
                    'komoditas_id'      => 1,
                    'kemiringan_lereng' => 5,
                    'ketinggian_tempat' => 30,
                    'curah_hujan'       => 650,
                    'ph_tanah'          => 6.2,
                    'jenis_tanah'       => 'tanah lempung',
                    'kedalaman_tanah'   => 40,
                    'irigasi'           => 100,
                    'kelas'             => 'S1',
                ],
                [
                    'id'                => 11,
                    'komoditas_id'      => 2,
                    'kemiringan_lereng' => 6,
                    'ketinggian_tempat' => 30,
                    'curah_hujan'       => 650,
                    'ph_tanah'          => 6.2,
                    'jenis_tanah'       => 'tanah kubi',
                    'kedalaman_tanah'   => 40,
                    'irigasi'           => 100,
                    'kelas'             => 'S2',
                ],
                [
                    'id'                => 12,
                    'komoditas_id'      => 4,
                    'kemiringan_lereng' => 20,
                    'ketinggian_tempat' => 300,
                    'curah_hujan'       => 400,
                    'ph_tanah'          => 4.8,
                    'jenis_tanah'       => 'tanah berpasir',
                    'kedalaman_tanah'   => 10,
                    'irigasi'           => 800,
                    'kelas'             => 'N',
                ],
            ],
            3,
        );

        $this->assertSame('S1', $result['prediction']['kelas']);
        $this->assertSame(3, $result['effective_k']);
        $this->assertSame(0.0, $result['neighbors'][0]['distance']);
        $this->assertSame(10, $result['neighbors'][0]['training_id']);
    }

    public function testMatchesManualCsvSampleResultsWhenKIs31(): void
    {
        $classifier = new KnnClassifier();
        $trainingRows = $this->loadTrainingRowsFromManualCsv();

        foreach ($this->manualCsvSamples() as $sample) {
            $result = $classifier->classify($sample['input'], $trainingRows, 31);
            $this->assertSame($sample['expected'], $result['prediction']['kelas'], $sample['name']);
            $this->assertSame(31, $result['effective_k'], $sample['name']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadTrainingRowsFromManualCsv(): array
    {
        $handle = fopen('/Users/faqihnurrahman/Downloads/data lahan.csv', 'rb');
        $this->assertNotFalse($handle);

        fgetcsv($handle, 0, ',');
        $rows = [];
        $id = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $first = strtolower(trim((string) ($row[0] ?? '')));
            if ($first === 'jenis tanaman uji') {
                break;
            }

            if ($first === '') {
                continue;
            }

            $rows[] = [
                'id'                => $id++,
                'komoditas_id'      => 0,
                'kemiringan_lereng' => (float) str_replace('%', '', (string) $row[1]),
                'ketinggian_tempat' => (int) $row[2],
                'curah_hujan'       => (int) $row[3],
                'ph_tanah'          => (float) str_replace(',', '.', (string) $row[4]),
                'jenis_tanah'       => $this->soilLabel((string) $row[5]),
                'kedalaman_tanah'   => (int) $row[6],
                'irigasi'           => (int) $row[7],
                'kelas'             => strtoupper(trim((string) $row[8])),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array{name: string, input: array<string, mixed>, expected: string}>
     */
    private function manualCsvSamples(): array
    {
        return [
            [
                'name' => 'tebu sample',
                'input' => [
                    'kemiringan_lereng' => 5,
                    'ketinggian_tempat' => 27,
                    'curah_hujan'       => 652,
                    'ph_tanah'          => 6.0,
                    'jenis_tanah'       => 'tanah lempung',
                    'kedalaman_tanah'   => 30,
                    'irigasi'           => 200,
                ],
                'expected' => 'S1',
            ],
            [
                'name' => 'jagung sample',
                'input' => [
                    'kemiringan_lereng' => 3,
                    'ketinggian_tempat' => 30,
                    'curah_hujan'       => 652,
                    'ph_tanah'          => 6.5,
                    'jenis_tanah'       => 'tanah lempung',
                    'kedalaman_tanah'   => 66,
                    'irigasi'           => 100,
                ],
                'expected' => 'S2',
            ],
            [
                'name' => 'padi sample',
                'input' => [
                    'kemiringan_lereng' => 7,
                    'ketinggian_tempat' => 45,
                    'curah_hujan'       => 652,
                    'ph_tanah'          => 6.0,
                    'jenis_tanah'       => 'tanah liat',
                    'kedalaman_tanah'   => 40,
                    'irigasi'           => 350,
                ],
                'expected' => 'S2',
            ],
            [
                'name' => 'cabe sample',
                'input' => [
                    'kemiringan_lereng' => 2,
                    'ketinggian_tempat' => 45,
                    'curah_hujan'       => 652,
                    'ph_tanah'          => 4.4,
                    'jenis_tanah'       => 'tanah lempung',
                    'kedalaman_tanah'   => 35,
                    'irigasi'           => 560,
                ],
                'expected' => 'S2',
            ],
        ];
    }

    private function soilLabel(string $value): string
    {
        return match (trim($value)) {
            '1' => 'tanah lempung',
            '2' => 'tanah kubi',
            '3' => 'tanah liat',
            '4' => 'tanah berpasir',
            default => strtolower(trim($value)),
        };
    }
}
