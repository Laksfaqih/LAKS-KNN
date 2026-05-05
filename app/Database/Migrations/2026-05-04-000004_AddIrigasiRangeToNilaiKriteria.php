<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIrigasiRangeToNilaiKriteria extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('nilai_kriteria')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('irigasi_min', 'nilai_kriteria')) {
            $fields['irigasi_min'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'curah_hujan_max',
            ];
        }

        if (! $this->db->fieldExists('irigasi_max', 'nilai_kriteria')) {
            $fields['irigasi_max'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'irigasi_min',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('nilai_kriteria', $fields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('nilai_kriteria')) {
            return;
        }

        $dropColumns = [];
        if ($this->db->fieldExists('irigasi_min', 'nilai_kriteria')) {
            $dropColumns[] = 'irigasi_min';
        }
        if ($this->db->fieldExists('irigasi_max', 'nilai_kriteria')) {
            $dropColumns[] = 'irigasi_max';
        }

        if ($dropColumns !== []) {
            $this->forge->dropColumn('nilai_kriteria', $dropColumns);
        }
    }
}
