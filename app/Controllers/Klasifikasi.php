<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Libraries\GeoPolygon;
use App\Libraries\KnnClassifier;
use App\Libraries\SoilTypeCatalog;
use App\Libraries\SuitabilityClassEvaluator;
use App\Models\DataLatihModel;
use App\Models\DataUjiModel;
use App\Models\HasilKlasifikasiDetailModel;
use App\Models\HasilKlasifikasiModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Klasifikasi extends BaseController
{
    private const SERVICE_AREA_BOUNDS = [
        'lat_min' => -7.7865,
        'lat_max' => -7.7300,
        'lng_min' => 114.1500,
        'lng_max' => 114.2150,
    ];

    private DataLatihModel $dataLatihModel;
    private DataUjiModel $dataUjiModel;
    private HasilKlasifikasiModel $hasilKlasifikasiModel;
    private HasilKlasifikasiDetailModel $hasilKlasifikasiDetailModel;
    private CommodityCatalog $commodityCatalog;
    private KnnClassifier $classifier;
    private SuitabilityClassEvaluator $classEvaluator;
    private GeoPolygon $geoPolygon;

    public function __construct()
    {
        $this->dataLatihModel = new DataLatihModel();
        $this->dataUjiModel = new DataUjiModel();
        $this->hasilKlasifikasiModel = new HasilKlasifikasiModel();
        $this->hasilKlasifikasiDetailModel = new HasilKlasifikasiDetailModel();
        $this->commodityCatalog = new CommodityCatalog();
        $this->classifier = new KnnClassifier();
        $this->classEvaluator = new SuitabilityClassEvaluator();
        $this->geoPolygon = new GeoPolygon();
    }

    public function index()
    {
        $recentResults = $this->db->table('hasil_klasifikasi hk')
            ->select('hk.id, hk.nilai_k, hk.rekomendasi_komoditas, du.nama_lahan, du.latitude, du.longitude')
            ->join('data_uji du', 'du.id = hk.data_uji_id')
            ->orderBy('hk.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return view('component/klasifikasi/index', [
            'title'             => 'Klasifikasi',
            'jenisTanahOptions' => SoilTypeCatalog::all(),
            'validation'        => service('validation'),
            'totalDataLatih'    => $this->dataLatihModel->countAll(),
            'recentResults'     => $recentResults,
        ]);
    }

    public function reverseGeocode()
    {
        $latitude = (float) $this->request->getPost('latitude');
        $longitude = (float) $this->request->getPost('longitude');

        if (! $this->isInsideServiceArea($latitude, $longitude)) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'Koordinat harus berada di area Curah Kalak.',
            ])->setStatusCode(422);
        }

        try {
            $address = $this->fetchAddressFromCoordinates($latitude, $longitude);
        } catch (\Throwable $exception) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => $exception->getMessage(),
            ])->setStatusCode(422);
        }

        return $this->response->setJSON([
            'ok' => true,
            'address' => $address,
        ]);
    }

    public function store()
    {
        if (! $this->validate($this->validationRules(), $this->validationMessages())) {
            return redirect()->to('/klasifikasi')->withInput();
        }

        $komoditas = $this->commodityCatalog->active();
        $trainingData = $this->trainingData($komoditas);
        $criteriaRows = $this->criteriaRows($komoditas);

        if ($trainingData === []) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', 'Data latih belum tersedia. Tambahkan data latih terlebih dahulu.');
        }

        try {
            $dataUjiPayload = $this->payload();
            if (! $this->isInsideServiceArea((float) $dataUjiPayload['latitude'], (float) $dataUjiPayload['longitude'])) {
                return redirect()->to('/klasifikasi')->withInput()->with('error', 'Area lahan harus berada di Curah Kalak, Kecamatan Jangkar, Kabupaten Situbondo.');
            }

            $requestedK = (int) $this->request->getPost('nilai_k');
            $classification = $this->classifier->classify($dataUjiPayload, $trainingData, $komoditas, $requestedK);
            $classResults = $this->classEvaluator->evaluateAll($dataUjiPayload, $komoditas, $criteriaRows);
        } catch (\Throwable $exception) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', $exception->getMessage());
        }

        $this->db->transStart();
        $dataUjiId = (int) $this->dataUjiModel->insert($dataUjiPayload, true);

        $headerPayload = [
            'data_uji_id'              => $dataUjiId,
            'rekomendasi_komoditas'    => $classification['recommendation']['nama'],
            'komoditas_rekomendasi_id' => $classification['recommendation']['komoditas_id'],
            'nilai_k'                  => $classification['effective_k'],
        ];

        $this->hasilKlasifikasiModel->insert($headerPayload);
        $hasilId = (int) $this->hasilKlasifikasiModel->getInsertID();

        foreach ($classResults as $result) {
            $this->hasilKlasifikasiDetailModel->insert([
                'hasil_klasifikasi_id' => $hasilId,
                'komoditas_id'         => $result['komoditas_id'],
                'kelas'                => $result['kelas'],
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
            ->select('hd.kelas, k.id AS komoditas_id, k.nama, k.warna_peta')
            ->join('komoditas k', 'k.id = hd.komoditas_id')
            ->where('hd.hasil_klasifikasi_id', $hasilId)
            ->orderBy('k.urutan_tampil', 'ASC')
            ->get()
            ->getResultArray();

        return view('component/klasifikasi/result', [
            'title'      => 'Hasil Klasifikasi',
            'hasil'      => $hasil,
            'dataUji'    => $dataUji,
            'komoditas'  => $komoditas,
            'detailRows' => $detailRows,
        ]);
    }

    private function payload(): array
    {
        $latitude = (float) $this->request->getPost('latitude');
        $longitude = (float) $this->request->getPost('longitude');
        $namaLahan = $this->fetchAddressFromCoordinates($latitude, $longitude);
        $polygonGeojson = $this->geoPolygon->squareGeoJsonFromPoint($latitude, $longitude, 50.0);

        return [
            'nama_lahan'         => $namaLahan,
            'kemiringan_lereng'  => $this->request->getPost('kemiringan_lereng'),
            'luas_lahan'         => $this->request->getPost('luas_lahan'),
            'ph_tanah'           => $this->request->getPost('ph_tanah'),
            'jenis_tanah'        => $this->request->getPost('jenis_tanah'),
            'ketinggian_tempat'  => $this->request->getPost('ketinggian_tempat'),
            'kedalaman_tanah'    => $this->request->getPost('kedalaman_tanah'),
            'curah_hujan'        => $this->request->getPost('curah_hujan'),
            'irigasi'            => $this->request->getPost('irigasi'),
            'latitude'           => number_format($latitude, 8, '.', ''),
            'longitude'          => number_format($longitude, 8, '.', ''),
            'polygon_geojson'    => $polygonGeojson,
            'geometry_source'    => 'coordinate_input_auto_polygon',
        ];
    }

    private function validationRules(): array
    {
        return [
            'latitude'           => 'required|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'longitude'          => 'required|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
            'kemiringan_lereng'  => 'required|decimal|greater_than_equal_to[0]',
            'luas_lahan'         => 'required|decimal|greater_than[0]',
            'ph_tanah'           => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[14]',
            'jenis_tanah'        => 'required|in_list[' . implode(',', SoilTypeCatalog::all()) . ']',
            'ketinggian_tempat'  => 'required|integer|greater_than_equal_to[0]',
            'kedalaman_tanah'    => 'required|integer|greater_than[0]',
            'curah_hujan'        => 'required|integer|greater_than[0]',
            'irigasi'            => 'required|integer|greater_than_equal_to[0]',
            'nilai_k'            => 'required|integer|greater_than[0]',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'latitude' => [
                'required'              => 'Latitude wajib diisi.',
                'decimal'               => 'Latitude harus berupa angka desimal.',
                'greater_than_equal_to' => 'Latitude minimal -90.',
                'less_than_equal_to'    => 'Latitude maksimal 90.',
            ],
            'longitude' => [
                'required'              => 'Longitude wajib diisi.',
                'decimal'               => 'Longitude harus berupa angka desimal.',
                'greater_than_equal_to' => 'Longitude minimal -180.',
                'less_than_equal_to'    => 'Longitude maksimal 180.',
            ],
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
            'irigasi' => [
                'required' => 'Irigasi wajib diisi.',
                'integer'  => 'Irigasi harus berupa bilangan bulat.',
            ],
            'nilai_k' => [
                'required' => 'Nilai K wajib diisi.',
                'integer'  => 'Nilai K harus berupa bilangan bulat.',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     * @return array<int, array<string, mixed>>
     */
    private function trainingData(array $komoditas): array
    {
        $ids = array_map(static fn(array $row): int => (int) $row['id'], $komoditas);
        if ($ids === []) {
            return [];
        }

        return $this->dataLatihModel
            ->whereIn('komoditas_id', $ids)
            ->findAll();
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     * @return array<int, array<string, mixed>>
     */
    private function criteriaRows(array $komoditas): array
    {
        $ids = array_map(static fn(array $row): int => (int) $row['id'], $komoditas);
        if ($ids === []) {
            return [];
        }

        return $this->db->table('nilai_kriteria')
            ->whereIn('komoditas_id', $ids)
            ->orderBy('FIELD(kelas, "S1", "S2", "S3", "N")', '', false)
            ->get()
            ->getResultArray();
    }

    private function isInsideServiceArea(float $latitude, float $longitude): bool
    {
        return $latitude >= self::SERVICE_AREA_BOUNDS['lat_min']
            && $latitude <= self::SERVICE_AREA_BOUNDS['lat_max']
            && $longitude >= self::SERVICE_AREA_BOUNDS['lng_min']
            && $longitude <= self::SERVICE_AREA_BOUNDS['lng_max'];
    }

    private function fetchAddressFromCoordinates(float $latitude, float $longitude): string
    {
        $query = http_build_query([
            'lat' => number_format($latitude, 8, '.', ''),
            'lon' => number_format($longitude, 8, '.', ''),
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'zoom' => 18,
        ]);

        $url = 'https://nominatim.openstreetmap.org/reverse?' . $query;
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: SistemKlasifikasiLahan/1.0\r\nAccept: application/json\r\n",
                'timeout' => 12,
            ],
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException('Gagal mengambil alamat otomatis dari koordinat.');
        }

        $decoded = json_decode($response, true);
        $address = trim((string) ($decoded['display_name'] ?? ''));
        if ($address === '') {
            throw new \RuntimeException('Alamat tidak ditemukan untuk koordinat tersebut.');
        }

        return $address;
    }
}
