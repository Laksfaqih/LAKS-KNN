<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorTrainingAndCriteriaSchema extends Migration
{
    public function up()
    {
        $this->clearLegacyData();
        $this->refactorDataLatih();
        $this->refactorNilaiKriteria();
        $this->refactorHasilKlasifikasi();
    }

    public function down()
    {
        // Irreversible schema refactor.
    }

    private function clearLegacyData(): void
    {
        if ($this->db->tableExists('data_latih')) {
            $this->db->table('data_latih')->emptyTable();
        }

        if ($this->db->tableExists('nilai_kriteria')) {
            $this->db->table('nilai_kriteria')->emptyTable();
        }
    }

    private function refactorDataLatih(): void
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        if ($this->db->tableExists('data_latih_komoditas')) {
            $this->forge->dropTable('data_latih_komoditas', true);
        }

        $fields = [];

        if (! $this->db->fieldExists('komoditas_id', 'data_latih')) {
            $fields['komoditas_id'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'after'      => 'curah_hujan',
            ];
        }

        if (! $this->db->fieldExists('latitude', 'data_latih')) {
            $fields['latitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => false,
                'after'      => 'komoditas_id',
            ];
        }

        if (! $this->db->fieldExists('longitude', 'data_latih')) {
            $fields['longitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => false,
                'after'      => 'latitude',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('data_latih', $fields);
        }

        $dropColumns = [];
        foreach (['kelas_tebu', 'kelas_cabai', 'kelas_jagung', 'kelas_padi'] as $column) {
            if ($this->db->fieldExists($column, 'data_latih')) {
                $dropColumns[] = $column;
            }
        }

        if ($dropColumns !== []) {
            $this->forge->dropColumn('data_latih', $dropColumns);
        }

        $foreignKeyExists = $this->db->query("
            SELECT COUNT(*) AS total
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'data_latih'
              AND CONSTRAINT_NAME = 'data_latih_komoditas_id_foreign'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ")->getRowArray();

        if ((int) ($foreignKeyExists['total'] ?? 0) === 0) {
            $this->db->query('ALTER TABLE `data_latih` ADD KEY `data_latih_komoditas_id_foreign` (`komoditas_id`)');
            $this->db->query('ALTER TABLE `data_latih` ADD CONSTRAINT `data_latih_komoditas_id_foreign` FOREIGN KEY (`komoditas_id`) REFERENCES `komoditas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
        }
    }

    private function refactorNilaiKriteria(): void
    {
        if (! $this->db->tableExists('nilai_kriteria')) {
            return;
        }

        if (! $this->db->fieldExists('kelas', 'nilai_kriteria')) {
            $this->forge->addColumn('nilai_kriteria', [
                'kelas' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 2,
                    'null'       => false,
                    'after'      => 'komoditas_id',
                ],
            ]);
        }

        $pairKeyExists = $this->db->query("
            SELECT COUNT(*) AS total
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'nilai_kriteria'
              AND INDEX_NAME = 'nilai_kriteria_komoditas_kelas_unique'
              AND NON_UNIQUE = 0
        ")->getRowArray();

        if ((int) ($pairKeyExists['total'] ?? 0) === 0) {
            $this->db->query('ALTER TABLE `nilai_kriteria` ADD UNIQUE KEY `nilai_kriteria_komoditas_kelas_unique` (`komoditas_id`, `kelas`)');
        }

        $pairKeyExists = $this->db->query("
            SELECT COUNT(*) AS total
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'nilai_kriteria'
              AND INDEX_NAME = 'nilai_kriteria_komoditas_kelas_unique'
              AND NON_UNIQUE = 0
        ")->getRowArray();

        $uniqueKeyExists = $this->db->query("
            SELECT COUNT(*) AS total
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'nilai_kriteria'
              AND INDEX_NAME = 'komoditas_id'
              AND NON_UNIQUE = 0
        ")->getRowArray();

        if ((int) ($pairKeyExists['total'] ?? 0) > 0 && (int) ($uniqueKeyExists['total'] ?? 0) > 0) {
            $this->db->query('ALTER TABLE `nilai_kriteria` DROP INDEX `komoditas_id`');
        }
    }

    private function refactorHasilKlasifikasi(): void
    {
        if (! $this->db->tableExists('hasil_klasifikasi')) {
            return;
        }

        $dropColumns = [];
        foreach (['kelas_tebu', 'kelas_cabai', 'kelas_jagung', 'kelas_padi'] as $column) {
            if ($this->db->fieldExists($column, 'hasil_klasifikasi')) {
                $dropColumns[] = $column;
            }
        }

        if ($dropColumns !== []) {
            $this->forge->dropColumn('hasil_klasifikasi', $dropColumns);
        }
    }
}
