<?php

namespace App\Database\Migrations;

use App\Libraries\SoilTypeCatalog;
use CodeIgniter\Database\Migration;

class RestrictSoilTypesToCanonicalSet extends Migration
{
    public function up()
    {
        $allowed = SoilTypeCatalog::all();

        $this->deleteInvalidRows('data_latih', $allowed);
        $this->deleteInvalidRows('data_uji', $allowed);
        $this->cleanupNilaiKriteria($allowed);
    }

    public function down()
    {
        // Irreversible cleanup migration.
    }

    /**
     * @param list<string> $allowed
     */
    private function deleteInvalidRows(string $table, array $allowed): void
    {
        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('jenis_tanah', $table)) {
            return;
        }

        $this->db->table($table)
            ->whereNotIn('jenis_tanah', $allowed)
            ->delete();
    }

    /**
     * @param list<string> $allowed
     */
    private function cleanupNilaiKriteria(array $allowed): void
    {
        if (! $this->db->tableExists('nilai_kriteria') || ! $this->db->fieldExists('jenis_tanah_json', 'nilai_kriteria')) {
            return;
        }

        $rows = $this->db->table('nilai_kriteria')
            ->select('id, jenis_tanah_json')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $decoded = json_decode((string) ($row['jenis_tanah_json'] ?? '[]'), true);
            $items = is_array($decoded) ? $decoded : [];
            $normalized = [];

            foreach ($items as $item) {
                $item = trim((string) $item);
                if ($item === '' || ! in_array($item, $allowed, true)) {
                    continue;
                }

                $normalized[$item] = $item;
            }

            $this->db->table('nilai_kriteria')
                ->where('id', (int) $row['id'])
                ->update([
                    'jenis_tanah_json' => json_encode(array_values($normalized), JSON_UNESCAPED_UNICODE),
                ]);
        }
    }
}
