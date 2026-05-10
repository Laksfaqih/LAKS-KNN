<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropCoordinatesFromDataLatih extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        $dropColumns = [];

        if ($this->db->fieldExists('latitude', 'data_latih')) {
            $dropColumns[] = 'latitude';
        }

        if ($this->db->fieldExists('longitude', 'data_latih')) {
            $dropColumns[] = 'longitude';
        }

        if ($dropColumns !== []) {
            $this->forge->dropColumn('data_latih', $dropColumns);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('latitude', 'data_latih')) {
            $fields['latitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => true,
                'default'    => null,
            ];
        }

        if (! $this->db->fieldExists('longitude', 'data_latih')) {
            $fields['longitude'] = [
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => true,
                'default'    => null,
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('data_latih', $fields);
        }
    }
}
