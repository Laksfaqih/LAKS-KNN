<?php

use App\Controllers\NilaiKriteria;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class NilaiKriteriaImportTest extends CIUnitTestCase
{
    public function testParseImportRowsSupportsKriteriaCsvFormat(): void
    {
        $controller = new NilaiKriteria();
        $handle = fopen('/Users/faqihnurrahman/Downloads/kriteria.csv', 'rb');

        $method = new ReflectionMethod($controller, 'parseImportRows');
        $method->setAccessible(true);

        [$rows, $errors, $commodityIds] = $method->invoke($controller, $handle, [
            'tebu' => 1,
            'jagung' => 2,
            'cabe' => 3,
            'cabai' => 3,
            'padi' => 4,
        ]);

        fclose($handle);

        $this->assertSame([], $errors);
        $this->assertCount(16, $rows);
        $this->assertSame([1, 2, 3, 4], $commodityIds);
        $this->assertSame('S1', $rows[0]['kelas']);
        $this->assertSame(json_encode(['tanah lempung'], JSON_UNESCAPED_UNICODE), $rows[0]['jenis_tanah_json']);
        $this->assertSame('N', $rows[3]['kelas']);
        $this->assertSame(null, $rows[3]['ph_min']);
        $this->assertSame(null, $rows[3]['ph_max']);
    }

    public function testIsSupportedBlockHeaderRecognizesLocalKriteriaHeader(): void
    {
        $controller = new NilaiKriteria();
        $method = new ReflectionMethod($controller, 'isSupportedBlockHeader');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, [
            'lereng',
            'elevasi/ketinggian',
            'curah hujan',
            'ph tanah',
            'jenis tanah',
            'kedalaman tanah',
            'irigasi',
            'kelas',
        ]));

        $this->assertTrue($method->invoke($controller, [
            'Lereng',
            'Elevasi Ketinggian',
            'Curah Hujan',
            'pH Tanah',
            'Jenis Tanah',
            'Kedalaman',
            'Irigasi',
            'Kelas',
        ]));

        $this->assertFalse($method->invoke($controller, [
            'komoditas',
            'kelas',
        ]));
    }

    public function testResolveSoilTypePrefersCodeMapping(): void
    {
        $controller = new NilaiKriteria();
        $method = new ReflectionMethod($controller, 'resolveSoilType');
        $method->setAccessible(true);

        $this->assertSame('tanah kubi', $method->invoke($controller, '2', 'tanah liat'));
        $this->assertSame('tanah berpasir', $method->invoke($controller, '', 'tanah berpasir'));
    }
}
