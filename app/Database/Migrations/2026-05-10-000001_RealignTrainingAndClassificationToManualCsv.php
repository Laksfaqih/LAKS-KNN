<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RealignTrainingAndClassificationToManualCsv extends Migration
{
    public function up()
    {
        $this->alterDataLatih();
        $this->alterDataUji();
        $this->alterHasilKlasifikasi();
        $this->createDataLatihLabelTable();
        $this->createDataUjiContohTable();
    }

    public function down()
    {
        if ($this->db->tableExists('data_uji_contoh')) {
            $this->forge->dropTable('data_uji_contoh', true);
        }

        if ($this->db->tableExists('data_latih_label')) {
            $this->forge->dropTable('data_latih_label', true);
        }
    }

    private function alterDataLatih(): void
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        $fields = [];

        if ($this->db->fieldExists('luas_lahan', 'data_latih')) {
            $fields['luas_lahan'] = [
                'name'       => 'luas_lahan',
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'default'    => null,
            ];
        }

        if ($this->db->fieldExists('latitude', 'data_latih')) {
            $fields['latitude'] = [
                'name'       => 'latitude',
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
                'default'    => null,
            ];
        }

        if ($this->db->fieldExists('longitude', 'data_latih')) {
            $fields['longitude'] = [
                'name'       => 'longitude',
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
                'default'    => null,
            ];
        }

        if ($fields !== []) {
            $this->forge->modifyColumn('data_latih', $fields);
        }
    }

    private function alterDataUji(): void
    {
        if (! $this->db->tableExists('data_uji')) {
            return;
        }

        $fields = [];

        if ($this->db->fieldExists('luas_lahan', 'data_uji')) {
            $fields['luas_lahan'] = [
                'name'       => 'luas_lahan',
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'default'    => null,
            ];
        }

        if ($fields !== []) {
            $this->forge->modifyColumn('data_uji', $fields);
        }
    }

    private function alterHasilKlasifikasi(): void
    {
        if (! $this->db->tableExists('hasil_klasifikasi')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('komoditas_target_id', 'hasil_klasifikasi')) {
            $fields['komoditas_target_id'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'komoditas_rekomendasi_id',
            ];
        }

        if (! $this->db->fieldExists('kelas_hasil', 'hasil_klasifikasi')) {
            $fields['kelas_hasil'] = [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'null'       => true,
                'after'      => 'komoditas_target_id',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('hasil_klasifikasi', $fields);
        }
    }

    private function createDataLatihLabelTable(): void
    {
        if ($this->db->tableExists('data_latih_label')) {
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
                'null'       => false,
            ],
            'kelas' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('data_latih_id');
        $this->forge->addForeignKey('data_latih_id', 'data_latih', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('data_latih_label', true);
    }

    private function createDataUjiContohTable(): void
    {
        if ($this->db->tableExists('data_uji_contoh')) {
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
                'null'       => false,
            ],
            'kemiringan_lereng' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
            ],
            'ph_tanah' => [
                'type'       => 'DECIMAL',
                'constraint' => '4,2',
                'null'       => false,
            ],
            'jenis_tanah' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'ketinggian_tempat' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'kedalaman_tanah' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'curah_hujan' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'irigasi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'kelas_ekspektasi' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('komoditas_id', 'komoditas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('data_uji_contoh', true);
    }
}
