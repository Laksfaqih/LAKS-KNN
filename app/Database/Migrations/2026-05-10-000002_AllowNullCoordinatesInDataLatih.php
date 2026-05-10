<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AllowNullCoordinatesInDataLatih extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        $fields = [];

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

    public function down()
    {
        if (! $this->db->tableExists('data_latih')) {
            return;
        }

        $fields = [];

        if ($this->db->fieldExists('latitude', 'data_latih')) {
            $fields['latitude'] = [
                'name'       => 'latitude',
                'type'       => 'DECIMAL',
                'constraint' => '10,8',
                'null'       => false,
            ];
        }

        if ($this->db->fieldExists('longitude', 'data_latih')) {
            $fields['longitude'] = [
                'name'       => 'longitude',
                'type'       => 'DECIMAL',
                'constraint' => '11,8',
                'null'       => false,
            ];
        }

        if ($fields !== []) {
            $this->forge->modifyColumn('data_latih', $fields);
        }
    }
}
