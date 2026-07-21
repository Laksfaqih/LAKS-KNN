<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;

class Dashboard extends BaseController
{
    private CommodityCatalog $commodityCatalog;

    public function __construct()
    {
        $this->commodityCatalog = new CommodityCatalog();
    }

    public function index()
    {
        $komoditas = $this->commodityCatalog->active();

        $chartRows = $this->db->table('komoditas k')
            ->select('k.id, k.nama, k.warna_peta, COUNT(hk.id) AS total')
            ->join('hasil_klasifikasi hk', 'hk.komoditas_rekomendasi_id = k.id', 'left')
            ->where('k.is_active', 1)
            ->groupBy('k.id')
            ->orderBy('k.urutan_tampil', 'ASC')
            ->get()
            ->getResultArray();

        $historyRows = $this->db->table('hasil_klasifikasi hk')
            ->select('hk.id, hk.nilai_k, hk.rekomendasi_komoditas, hk.komoditas_rekomendasi_id, du.nama_lahan, du.created_at AS tanggal_input')
            ->join('data_uji du', 'du.id = hk.data_uji_id')
            ->orderBy('hk.id', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        $detailRows = $this->db->table('hasil_klasifikasi_detail hd')
            ->select('hd.hasil_klasifikasi_id, hd.kelas, hd.komoditas_id')
            ->whereIn('hd.hasil_klasifikasi_id', array_map(static fn(array $row): int => (int) $row['id'], $historyRows ?: [['id' => 0]]))
            ->get()
            ->getResultArray();

        $detailByHeader = [];
        foreach ($detailRows as $detail) {
            $detailByHeader[(int) $detail['hasil_klasifikasi_id']][(int) $detail['komoditas_id']] = $detail['kelas'];
        }

        foreach ($historyRows as &$row) {
            $detailMap = $detailByHeader[(int) $row['id']] ?? [];
            $row['hasil_detail'] = $detailMap;
        }

        $miniMapItems = $this->db->table('hasil_klasifikasi hk')
            ->select('hk.rekomendasi_komoditas, du.nama_lahan, du.polygon_geojson, k.warna_peta')
            ->join('data_uji du', 'du.id = hk.data_uji_id')
            ->join('komoditas k', 'k.id = hk.komoditas_rekomendasi_id', 'left')
            ->where('du.polygon_geojson IS NOT NULL', null, false)
            ->orderBy('hk.id', 'DESC')
            ->limit(12)
            ->get()
            ->getResultArray();

        return view('component/dashboard', [
            'title'          => 'Dashboard',
            'totalDataLatih' => $this->db->table('data_latih')->countAllResults(),
            'totalDataUji'   => $this->db->table('data_uji')->countAllResults(),
            'totalHasil'     => $this->db->table('hasil_klasifikasi')->countAllResults(),
            'chartItems'     => $chartRows,
            'historyRows'    => $historyRows,
            'komoditas'      => $komoditas,
            'miniMapItems'   => $miniMapItems,
        ]);
    }
}
