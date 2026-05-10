<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveAreaFieldsFromNilaiKriteria extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('nilai_kriteria')) {
            return;
        }

        $dropColumns = [];
        foreach (['luas_min', 'luas_max'] as $column) {
            if ($this->db->fieldExists($column, 'nilai_kriteria')) {
                $dropColumns[] = $column;
            }
        }

        if ($dropColumns !== []) {
            $this->forge->dropColumn('nilai_kriteria', $dropColumns);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('nilai_kriteria')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('luas_min', 'nilai_kriteria')) {
            $fields['luas_min'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'kemiringan_max',
            ];
        }

        if (! $this->db->fieldExists('luas_max', 'nilai_kriteria')) {
            $fields['luas_max'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'after'      => 'luas_min',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('nilai_kriteria', $fields);
        }
    }
}
