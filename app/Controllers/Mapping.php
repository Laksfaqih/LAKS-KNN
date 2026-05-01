<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;

class Mapping extends BaseController
{
    private CommodityCatalog $commodityCatalog;

    public function __construct()
    {
        $this->commodityCatalog = new CommodityCatalog();
    }

    public function index()
    {
        $headers = $this->db->table('hasil_klasifikasi hk')
            ->select('hk.id, hk.rekomendasi_komoditas, hk.komoditas_rekomendasi_id, du.nama_lahan, du.kemiringan_lereng, du.luas_lahan, du.ph_tanah, du.jenis_tanah, du.ketinggian_tempat, du.kedalaman_tanah, du.curah_hujan, du.polygon_geojson')
            ->join('data_uji du', 'du.id = hk.data_uji_id')
            ->where('du.polygon_geojson IS NOT NULL', null, false)
            ->orderBy('hk.id', 'DESC')
            ->get()
            ->getResultArray();

        $details = $this->db->table('hasil_klasifikasi_detail hd')
            ->select('hd.hasil_klasifikasi_id, hd.kelas, k.nama, k.slug, k.warna_peta')
            ->join('komoditas k', 'k.id = hd.komoditas_id')
            ->orderBy('k.urutan_tampil', 'ASC')
            ->get()
            ->getResultArray();

        $detailsByHeader = [];
        foreach ($details as $detail) {
            $detailsByHeader[(int) $detail['hasil_klasifikasi_id']][] = $detail;
        }

        $commodityMap = $this->commodityCatalog->idMap(false);
        foreach ($headers as &$row) {
            $recommended = $commodityMap[(int) ($row['komoditas_rekomendasi_id'] ?? 0)] ?? null;
            $row['rekomendasi_warna'] = $recommended['warna_peta'] ?? '#64748b';
            $row['hasil_detail'] = $detailsByHeader[(int) $row['id']] ?? [];
        }

        return view('component/mapping/index', [
            'title'       => 'Mapping',
            'mapPolygons' => $headers,
            'legendItems' => $this->commodityCatalog->active(),
        ]);
    }
}
