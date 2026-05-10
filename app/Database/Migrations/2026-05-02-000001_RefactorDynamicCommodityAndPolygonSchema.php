<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class RefactorDynamicCommodityAndPolygonSchema extends Migration
{
    public function up()
    {
        $this->createKomoditasTable();
        $this->createNilaiKriteriaTable();
        $this->createDataLatihKomoditasTable();
        $this->createHasilKlasifikasiDetailTable();
        $this->alterDataUjiTable();
        $this->alterHasilKlasifikasiTable();
        $this->seedDefaultKomoditas();
        $this->backfillDataLatihKomoditas();
        $this->backfillHasilKlasifikasiDetail();
        $this->migrateOldPointToPolygon();
    }

    public function down()
    {
        if ($this->db->fieldExists('komoditas_rekomendasi_id', 'hasil_klasifikasi')) {
            $this->forge->dropForeignKey('hasil_klasifikasi', 'hasil_klasifikasi_komoditas_rekomendasi_id_foreign');
            $this->forge->dropColumn('hasil_klasifikasi', 'komoditas_rekomendasi_id');
        }

        if ($this->db->fieldExists('geometry_source', 'data_uji')) {
            $this->forge->dropColumn('data_uji', ['polygon_geojson', 'geometry_source']);
        }

        $this->forge->dropTable('hasil_klasifikasi_detail', true);
        $this->forge->dropTable('data_latih_komoditas', true);
        $this->forge->dropTable('nilai_kriteria', true);
        $this->forge->dropTable('komoditas', true);
    }

    private function createKomoditasTable(): void
    {
        if ($this->db->tableExists('komoditas')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'warna_peta' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'urutan_tampil' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('komoditas', true);
    }

    private function createNilaiKriteriaTable(): void
    {
        if ($this->db->tableExists('nilai_kriteria')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'komoditas_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kemiringan_min' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'kemiringan_max' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'luas_min'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'luas_max'       => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'ph_min'         => ['type' => 'DECIMAL', 'constraint' => '4,2', 'null' => true],
            'ph_max'         => ['type' => 'DECIMAL', 'constraint' => '4,2', 'null' => true],
            'ketinggian_min' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'ketinggian_max' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'kedalaman_min'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'kedalaman_max'  => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'curah_hujan_min' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'curah_hujan_max' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'jenis_tanah_json' => ['type' => 'LONGTEXT', 'null' => true],
            'catatan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('komoditas_id');
        $this->forge->addForeignKey('komoditas_id', 'komoditas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('nilai_kriteria', true);
    }

    private function createDataLatihKomoditasTable(): void
    {
        if ($this->db->tableExists('data_latih_komoditas')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'data_latih_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'komoditas_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kelas' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['data_latih_id', 'komoditas_id']);
        $this->forge->addForeignKey('data_latih_id', 'data_latih', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('komoditas_id', 'komoditas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('data_latih_komoditas', true);
    }

    private function createHasilKlasifikasiDetailTable(): void
    {
        if ($this->db->tableExists('hasil_klasifikasi_detail')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'hasil_klasifikasi_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'komoditas_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kelas' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['hasil_klasifikasi_id', 'komoditas_id']);
        $this->forge->addForeignKey('hasil_klasifikasi_id', 'hasil_klasifikasi', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('komoditas_id', 'komoditas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hasil_klasifikasi_detail', true);
    }

    private function alterDataUjiTable(): void
    {
        $fields = [];
        if (! $this->db->fieldExists('polygon_geojson', 'data_uji')) {
            $fields['polygon_geojson'] = ['type' => 'LONGTEXT', 'null' => true, 'after' => 'longitude'];
        }
        if (! $this->db->fieldExists('geometry_source', 'data_uji')) {
            $fields['geometry_source'] = ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'polygon_geojson'];
        }

        if ($fields !== []) {
            $this->forge->addColumn('data_uji', $fields);
        }
    }

    private function alterHasilKlasifikasiTable(): void
    {
        if (! $this->db->fieldExists('komoditas_rekomendasi_id', 'hasil_klasifikasi')) {
            $this->forge->addColumn('hasil_klasifikasi', [
                'komoditas_rekomendasi_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'rekomendasi_komoditas',
                ],
            ]);
            $this->forge->addForeignKey('komoditas_rekomendasi_id', 'komoditas', 'id', 'SET NULL', 'CASCADE');
            $this->forge->processIndexes('hasil_klasifikasi');
        }
    }

    private function seedDefaultKomoditas(): void
    {
        $existing = $this->db->table('komoditas')->countAllResults();
        if ($existing > 0) {
            return;
        }

        $rows = [
            ['nama' => 'Tebu', 'slug' => 'tebu', 'warna_peta' => '#4CAF50', 'urutan_tampil' => 1, 'is_active' => 1],
            ['nama' => 'Jagung', 'slug' => 'jagung', 'warna_peta' => '#FFEB3B', 'urutan_tampil' => 2, 'is_active' => 1],
            ['nama' => 'Cabe', 'slug' => 'cabe', 'warna_peta' => '#EF6C00', 'urutan_tampil' => 3, 'is_active' => 1],
            ['nama' => 'Padi', 'slug' => 'padi', 'warna_peta' => '#F44336', 'urutan_tampil' => 4, 'is_active' => 1],
        ];

        $this->db->table('komoditas')->insertBatch($rows);
    }

    private function backfillDataLatihKomoditas(): void
    {
        $count = $this->db->table('data_latih_komoditas')->countAllResults();
        if ($count > 0 || ! $this->db->tableExists('data_latih')) {
            return;
        }

        $map = $this->commodityIdMap();
        if ($map === []) {
            return;
        }

        $rows = $this->db->table('data_latih')->get()->getResultArray();
        $batch = [];
        foreach ($rows as $row) {
            $batch[] = ['data_latih_id' => $row['id'], 'komoditas_id' => $map['tebu'] ?? null, 'kelas' => $row['kelas_tebu']];
            $batch[] = ['data_latih_id' => $row['id'], 'komoditas_id' => $map['jagung'] ?? null, 'kelas' => $row['kelas_jagung']];
            $batch[] = ['data_latih_id' => $row['id'], 'komoditas_id' => $map['cabe'] ?? null, 'kelas' => $row['kelas_cabai']];
            $batch[] = ['data_latih_id' => $row['id'], 'komoditas_id' => $map['padi'] ?? null, 'kelas' => $row['kelas_padi']];
        }

        $batch = array_values(array_filter($batch, static fn(array $row): bool => $row['komoditas_id'] !== null));
        if ($batch !== []) {
            $this->db->table('data_latih_komoditas')->insertBatch($batch);
        }
    }

    private function backfillHasilKlasifikasiDetail(): void
    {
        $count = $this->db->table('hasil_klasifikasi_detail')->countAllResults();
        $map = $this->commodityIdMap();
        if ($count > 0 || $map === []) {
            return;
        }

        $rows = $this->db->table('hasil_klasifikasi')->get()->getResultArray();
        $batch = [];
        foreach ($rows as $row) {
            $batch[] = ['hasil_klasifikasi_id' => $row['id'], 'komoditas_id' => $map['tebu'] ?? null, 'kelas' => $row['kelas_tebu']];
            $batch[] = ['hasil_klasifikasi_id' => $row['id'], 'komoditas_id' => $map['jagung'] ?? null, 'kelas' => $row['kelas_jagung']];
            $batch[] = ['hasil_klasifikasi_id' => $row['id'], 'komoditas_id' => $map['cabe'] ?? null, 'kelas' => $row['kelas_cabai']];
            $batch[] = ['hasil_klasifikasi_id' => $row['id'], 'komoditas_id' => $map['padi'] ?? null, 'kelas' => $row['kelas_padi']];

            $recommendedSlug = $this->mapOldRecommendationToSlug((string) ($row['rekomendasi_komoditas'] ?? ''));
            if ($recommendedSlug !== null && isset($map[$recommendedSlug])) {
                $this->db->table('hasil_klasifikasi')
                    ->where('id', $row['id'])
                    ->update(['komoditas_rekomendasi_id' => $map[$recommendedSlug]]);
            }
        }

        $batch = array_values(array_filter($batch, static fn(array $row): bool => $row['komoditas_id'] !== null));
        if ($batch !== []) {
            $this->db->table('hasil_klasifikasi_detail')->insertBatch($batch);
        }
    }

    private function migrateOldPointToPolygon(): void
    {
        if (! $this->db->tableExists('data_uji')) {
            return;
        }

        $fields = $this->db->getFieldNames('data_uji');
        if (
            ! in_array('polygon_geojson', $fields, true)
            || ! in_array('latitude', $fields, true)
            || ! in_array('longitude', $fields, true)
        ) {
            return;
        }

        $rows = $this->db->table('data_uji')
            ->where('polygon_geojson', null)
            ->where('latitude IS NOT NULL', null, false)
            ->where('longitude IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $lat = (float) $row['latitude'];
            $lng = (float) $row['longitude'];
            $delta = 0.0005;

            $polygon = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [$lng - $delta, $lat - $delta],
                        [$lng + $delta, $lat - $delta],
                        [$lng + $delta, $lat + $delta],
                        [$lng - $delta, $lat + $delta],
                        [$lng - $delta, $lat - $delta],
                    ]],
                ],
                'properties' => [
                    'migrated' => true,
                ],
            ];

            $this->db->table('data_uji')
                ->where('id', $row['id'])
                ->update([
                    'polygon_geojson' => json_encode($polygon, JSON_UNESCAPED_SLASHES),
                    'geometry_source' => 'migrated_point',
                ]);
        }
    }

    /**
     * @return array<string, int>
     */
    private function commodityIdMap(): array
    {
        $rows = $this->db->table('komoditas')->select('id, slug')->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['slug']] = (int) $row['id'];
        }

        return $map;
    }

    private function mapOldRecommendationToSlug(string $value): ?string
    {
        return match (strtolower(trim($value))) {
            'tebu' => 'tebu',
            'jagung' => 'jagung',
            'cabai', 'cabe' => 'cabe',
            'padi' => 'padi',
            default => null,
        };
    }
}
