<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropCommoditySlug extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('komoditas') || ! $this->db->fieldExists('slug', 'komoditas')) {
            return;
        }

        $slugIndexExists = $this->db->query("
            SELECT COUNT(*) AS total
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'komoditas'
              AND INDEX_NAME = 'slug'
        ")->getRowArray();

        if ((int) ($slugIndexExists['total'] ?? 0) > 0) {
            $this->db->query('ALTER TABLE `komoditas` DROP INDEX `slug`');
        }

        $this->forge->dropColumn('komoditas', 'slug');
    }

    public function down()
    {
        if (! $this->db->tableExists('komoditas') || $this->db->fieldExists('slug', 'komoditas')) {
            return;
        }

        $this->forge->addColumn('komoditas', [
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'nama',
            ],
        ]);
    }
}
