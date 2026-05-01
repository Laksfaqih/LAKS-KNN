<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Libraries\GeoPolygon;
use App\Libraries\KnnClassifier;
use App\Models\DataLatihKomoditasModel;
use App\Models\DataLatihModel;
use App\Models\DataUjiModel;
use App\Models\HasilKlasifikasiDetailModel;
use App\Models\HasilKlasifikasiModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Klasifikasi extends BaseController
{
    private const JENIS_TANAH_OPTIONS = [
        'Aluvial',
        'Andosol',
        'Grumosol',
        'Latosol',
        'Mediteran',
        'Organosol',
        'Podsolik',
        'Regosol',
    ];

    private DataLatihModel $dataLatihModel;
    private DataLatihKomoditasModel $dataLatihKomoditasModel;
    private DataUjiModel $dataUjiModel;
    private HasilKlasifikasiModel $hasilKlasifikasiModel;
    private HasilKlasifikasiDetailModel $hasilKlasifikasiDetailModel;
    private CommodityCatalog $commodityCatalog;
    private KnnClassifier $classifier;
    private GeoPolygon $geoPolygon;

    public function __construct()
    {
        $this->dataLatihModel = new DataLatihModel();
        $this->dataLatihKomoditasModel = new DataLatihKomoditasModel();
        $this->dataUjiModel = new DataUjiModel();
        $this->hasilKlasifikasiModel = new HasilKlasifikasiModel();
        $this->hasilKlasifikasiDetailModel = new HasilKlasifikasiDetailModel();
        $this->commodityCatalog = new CommodityCatalog();
        $this->classifier = new KnnClassifier();
        $this->geoPolygon = new GeoPolygon();
    }

    public function index()
    {
        $recentResults = $this->db->table('hasil_klasifikasi hk')
            ->select('hk.id, hk.nilai_k, hk.rekomendasi_komoditas, du.nama_lahan')
            ->join('data_uji du', 'du.id = hk.data_uji_id')
            ->orderBy('hk.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return view('component/klasifikasi/index', [
            'title'             => 'Klasifikasi',
            'jenisTanahOptions' => self::JENIS_TANAH_OPTIONS,
            'validation'        => service('validation'),
            'totalDataLatih'    => $this->dataLatihModel->countAll(),
            'recentResults'     => $recentResults,
        ]);
    }

    public function store()
    {
        if (! $this->validate($this->validationRules(), $this->validationMessages())) {
            return redirect()->to('/klasifikasi')->withInput();
        }

        $komoditas = $this->commodityCatalog->active();
        $trainingData = $this->trainingData();

        if ($trainingData === []) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', 'Data latih belum tersedia. Tambahkan data latih terlebih dahulu.');
        }

        try {
            $dataUjiPayload = $this->payload();
            $requestedK = (int) $this->request->getPost('nilai_k');
            $classification = $this->classifier->classify($dataUjiPayload, $trainingData, $komoditas, $requestedK);
        } catch (\Throwable $exception) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', $exception->getMessage());
        }

        $this->db->transStart();
        $dataUjiId = (int) $this->dataUjiModel->insert($dataUjiPayload, true);

        $headerPayload = [
            'data_uji_id'               => $dataUjiId,
            'rekomendasi_komoditas'     => $classification['recommendation']['nama'],
            'komoditas_rekomendasi_id'  => $classification['recommendation']['komoditas_id'],
            'nilai_k'                   => $classification['effective_k'],
            'kelas_tebu'                => $this->legacyClassBySlug($classification['predictions'], 'tebu'),
            'kelas_cabai'               => $this->legacyClassBySlug($classification['predictions'], 'cabe'),
            'kelas_jagung'              => $this->legacyClassBySlug($classification['predictions'], 'jagung'),
            'kelas_padi'                => $this->legacyClassBySlug($classification['predictions'], 'padi'),
        ];

        $this->hasilKlasifikasiModel->insert($headerPayload);
        $hasilId = (int) $this->hasilKlasifikasiModel->getInsertID();

        foreach ($classification['predictions'] as $prediction) {
            $this->hasilKlasifikasiDetailModel->insert([
                'hasil_klasifikasi_id' => $hasilId,
                'komoditas_id'         => $prediction['komoditas_id'],
                'kelas'                => $prediction['kelas'],
            ]);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', 'Hasil klasifikasi gagal disimpan.');
        }

        return redirect()->to('/klasifikasi/hasil/' . $hasilId)->with('success', 'Klasifikasi berhasil diproses.');
    }

    public function show(int $hasilId)
    {
        $hasil = $this->hasilKlasifikasiModel->find($hasilId);
        if ($hasil === null) {
            throw PageNotFoundException::forPageNotFound('Hasil klasifikasi tidak ditemukan.');
        }

        $dataUji = $this->dataUjiModel->find((int) $hasil['data_uji_id']);
        if ($dataUji === null) {
            throw PageNotFoundException::forPageNotFound('Data uji tidak ditemukan.');
        }

        $komoditas = $this->commodityCatalog->active();
        $detailRows = $this->db->table('hasil_klasifikasi_detail hd')
            ->select('hd.kelas, k.id AS komoditas_id, k.nama, k.slug, k.warna_peta')
            ->join('komoditas k', 'k.id = hd.komoditas_id')
            ->where('hd.hasil_klasifikasi_id', $hasilId)
            ->orderBy('k.urutan_tampil', 'ASC')
            ->get()
            ->getResultArray();

        return view('component/klasifikasi/result', [
            'title'          => 'Hasil Klasifikasi',
            'hasil'          => $hasil,
            'dataUji'        => $dataUji,
            'komoditas'      => $komoditas,
            'detailRows'     => $detailRows,
        ]);
    }

    private function payload(): array
    {
        $namaLahan = trim((string) $this->request->getPost('nama_lahan'));
        $polygonGeojson = trim((string) $this->request->getPost('polygon_geojson'));
        $centroid = $this->geoPolygon->centroidFromGeoJson($polygonGeojson);

        return [
            'nama_lahan'         => $namaLahan === '' ? null : $namaLahan,
            'kemiringan_lereng'  => $this->request->getPost('kemiringan_lereng'),
            'luas_lahan'         => $this->request->getPost('luas_lahan'),
            'ph_tanah'           => $this->request->getPost('ph_tanah'),
            'jenis_tanah'        => $this->request->getPost('jenis_tanah'),
            'ketinggian_tempat'  => $this->request->getPost('ketinggian_tempat'),
            'kedalaman_tanah'    => $this->request->getPost('kedalaman_tanah'),
            'curah_hujan'        => $this->request->getPost('curah_hujan'),
            'latitude'           => number_format($centroid['latitude'], 8, '.', ''),
            'longitude'          => number_format($centroid['longitude'], 8, '.', ''),
            'polygon_geojson'    => $polygonGeojson,
            'geometry_source'    => 'drawn_polygon',
        ];
    }

    private function validationRules(): array
    {
        return [
            'nama_lahan'         => 'permit_empty|max_length[100]',
            'kemiringan_lereng'  => 'required|decimal|greater_than_equal_to[0]',
            'luas_lahan'         => 'required|decimal|greater_than[0]',
            'ph_tanah'           => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[14]',
            'jenis_tanah'        => 'required|in_list[' . implode(',', self::JENIS_TANAH_OPTIONS) . ']',
            'ketinggian_tempat'  => 'required|integer|greater_than_equal_to[0]',
            'kedalaman_tanah'    => 'required|integer|greater_than[0]',
            'curah_hujan'        => 'required|integer|greater_than[0]',
            'nilai_k'            => 'required|integer|greater_than[0]',
            'polygon_geojson'    => 'required',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'nama_lahan' => ['max_length' => 'Nama lahan maksimal 100 karakter.'],
            'kemiringan_lereng' => [
                'required' => 'Kemiringan lereng wajib diisi.',
                'decimal'  => 'Kemiringan lereng harus berupa angka desimal.',
            ],
            'luas_lahan' => [
                'required' => 'Luas lahan wajib diisi.',
                'decimal'  => 'Luas lahan harus berupa angka desimal.',
            ],
            'ph_tanah' => [
                'required'           => 'pH tanah wajib diisi.',
                'decimal'            => 'pH tanah harus berupa angka desimal.',
                'less_than_equal_to' => 'pH tanah maksimal 14.',
            ],
            'jenis_tanah' => [
                'required' => 'Jenis tanah wajib dipilih.',
                'in_list'  => 'Jenis tanah tidak valid.',
            ],
            'ketinggian_tempat' => [
                'required' => 'Ketinggian tempat wajib diisi.',
                'integer'  => 'Ketinggian tempat harus berupa bilangan bulat.',
            ],
            'kedalaman_tanah' => [
                'required' => 'Kedalaman tanah wajib diisi.',
                'integer'  => 'Kedalaman tanah harus berupa bilangan bulat.',
            ],
            'curah_hujan' => [
                'required' => 'Curah hujan wajib diisi.',
                'integer'  => 'Curah hujan harus berupa bilangan bulat.',
            ],
            'nilai_k' => [
                'required' => 'Nilai K wajib diisi.',
                'integer'  => 'Nilai K harus berupa bilangan bulat.',
            ],
            'polygon_geojson' => [
                'required' => 'Area lahan wajib digambar di peta.',
            ],
        ];
    }

    private function trainingData(): array
    {
        $rows = $this->dataLatihModel->findAll();
        if ($rows === []) {
            return [];
        }

        $labels = [];
        $detailRows = $this->dataLatihKomoditasModel
            ->whereIn('data_latih_id', array_map(static fn(array $row): int => (int) $row['id'], $rows))
            ->findAll();

        foreach ($detailRows as $detail) {
            $labels[(int) $detail['data_latih_id']][(int) $detail['komoditas_id']] = (string) $detail['kelas'];
        }

        foreach ($rows as &$row) {
            $row['commodity_labels'] = $labels[(int) $row['id']] ?? [];
        }

        return $rows;
    }

    private function legacyClassBySlug(array $predictions, string $slug): string
    {
        foreach ($predictions as $prediction) {
            if (($prediction['slug'] ?? '') === $slug) {
                return (string) $prediction['kelas'];
            }
        }

        return 'N';
    }
}
