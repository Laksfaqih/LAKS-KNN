<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIrigasiToDataUji extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('data_uji')) {
            return;
        }

        if ($this->db->fieldExists('irigasi', 'data_uji')) {
            return;
        }

        $this->forge->addColumn('data_uji', [
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
        if (! $this->db->tableExists('data_uji')) {
            return;
        }

        if ($this->db->fieldExists('irigasi', 'data_uji')) {
            $this->forge->dropColumn('data_uji', 'irigasi');
        }
    }
}
