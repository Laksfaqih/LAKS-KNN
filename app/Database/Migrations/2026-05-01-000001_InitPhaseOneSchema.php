<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class InitPhaseOneSchema extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'nama_lengkap' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'kemiringan_lereng' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
            ],
            'luas_lahan' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
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
            'kelas_tebu' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'kelas_cabai' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'kelas_jagung' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'kelas_padi' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('data_latih', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nama_lahan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'kemiringan_lereng' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => false,
            ],
            'luas_lahan' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
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
            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
            ],
            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('data_uji', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'data_uji_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'kelas_tebu' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'kelas_cabai' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'kelas_jagung' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'kelas_padi' => [
                'type'       => 'ENUM',
                'constraint' => ['S1', 'S2', 'S3', 'N'],
                'null'       => false,
            ],
            'rekomendasi_komoditas' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'nilai_k' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 3,
                'null'       => false,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('data_uji_id');
        $this->forge->addForeignKey('data_uji_id', 'data_uji', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hasil_klasifikasi', true);
    }

    public function down()
    {
        $this->forge->dropTable('hasil_klasifikasi', true);
        $this->forge->dropTable('data_uji', true);
        $this->forge->dropTable('data_latih', true);
        $this->forge->dropTable('users', true);
    }
}
