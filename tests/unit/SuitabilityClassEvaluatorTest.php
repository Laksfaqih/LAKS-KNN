<?php

use App\Libraries\SuitabilityClassEvaluator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SuitabilityClassEvaluatorTest extends CIUnitTestCase
{
    public function testSelectsHighestMatchingClass(): void
    {
        $evaluator = new SuitabilityClassEvaluator();

        $result = $evaluator->evaluateAll(
            [
                'kemiringan_lereng' => 8,
                'ph_tanah' => 6.5,
                'jenis_tanah' => 'tanah liat',
                'ketinggian_tempat' => 400,
                'kedalaman_tanah' => 90,
                'curah_hujan' => 1800,
            ],
            [[
                'id' => 1,
                'nama' => 'Padi',
                'warna_peta' => '#00aa00',
            ]],
            [
                [
                    'komoditas_id' => 1,
                    'kelas' => 'S2',
                    'kemiringan_min' => '0',
                    'kemiringan_max' => '15',
                    'ph_min' => '5.5',
                    'ph_max' => '7.0',
                    'ketinggian_min' => null,
                    'ketinggian_max' => null,
                    'kedalaman_min' => null,
                    'kedalaman_max' => null,
                    'curah_hujan_min' => null,
                    'curah_hujan_max' => null,
                    'jenis_tanah_json' => json_encode(['tanah liat']),
                ],
                [
                    'komoditas_id' => 1,
                    'kelas' => 'S1',
                    'kemiringan_min' => '0',
                    'kemiringan_max' => '10',
                    'ph_min' => '6.0',
                    'ph_max' => '7.0',
                    'ketinggian_min' => null,
                    'ketinggian_max' => null,
                    'kedalaman_min' => null,
                    'kedalaman_max' => null,
                    'curah_hujan_min' => null,
                    'curah_hujan_max' => null,
                    'jenis_tanah_json' => json_encode(['tanah liat']),
                ],
            ],
        );

        $this->assertSame('S1', $result[1]['kelas']);
    }
}
