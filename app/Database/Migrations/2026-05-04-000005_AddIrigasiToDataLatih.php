<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIrigasiToDataLatih extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        if ($this->db->fieldExists('irigasi', 'data_latih')) {
            return;
        }

        $this->forge->addColumn('data_latih', [
            'irigasi' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
                'after'      => 'curah_hujan',
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        if ($this->db->fieldExists('irigasi', 'data_latih')) {
            $this->forge->dropColumn('data_latih', 'irigasi');
        }
    }
}
