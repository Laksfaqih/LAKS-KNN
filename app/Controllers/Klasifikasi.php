<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Libraries\GeoPolygon;
use App\Libraries\EvaluationMetrics;
use App\Libraries\KnnClassifier;
use App\Libraries\SoilTypeCatalog;
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
    private GeoPolygon $geoPolygon;
    private EvaluationMetrics $metrics;

    public function __construct()
    {
        $this->dataLatihModel = new DataLatihModel();
        $this->dataUjiModel = new DataUjiModel();
        $this->hasilKlasifikasiModel = new HasilKlasifikasiModel();
        $this->hasilKlasifikasiDetailModel = new HasilKlasifikasiDetailModel();
        $this->commodityCatalog = new CommodityCatalog();
        $this->classifier = new KnnClassifier();
        $this->geoPolygon = new GeoPolygon();
        $this->metrics = new EvaluationMetrics();
    }

    public function index()
    {
        $recentResults = $this->db->table('hasil_klasifikasi hk')
            ->select('hk.id, hk.nilai_k, hk.rekomendasi_komoditas, hk.kelas_hasil, k.nama AS nama_komoditas_target, du.nama_lahan, du.latitude, du.longitude')
            ->join('data_uji du', 'du.id = hk.data_uji_id')
            ->join('komoditas k', 'k.id = hk.komoditas_target_id', 'left')
            ->orderBy('hk.id', 'DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

        return view('component/klasifikasi/index', [
            'title'             => 'Klasifikasi',
            'jenisTanahOptions' => SoilTypeCatalog::all(),
            'komoditasOptions'  => $this->commodityCatalog->active(),
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

        $commodity = $this->selectedCommodity();
        if ($commodity === null) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', 'Tanaman yang dipilih tidak ditemukan.');
        }

        $trainingData = $this->trainingData();
        if ($trainingData === []) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', 'Data latih aktif belum tersedia.');
        }

        try {
            $dataUjiPayload = $this->payload();
            if (! $this->isInsideServiceArea((float) $dataUjiPayload['latitude'], (float) $dataUjiPayload['longitude'])) {
                return redirect()->to('/klasifikasi')->withInput()->with('error', 'Area lahan harus berada di Curah Kalak, Kecamatan Jangkar, Kabupaten Situbondo.');
            }

            $requestedK = (int) $this->request->getPost('nilai_k');
            $classification = $this->classifier->classify($dataUjiPayload, $trainingData, $requestedK);
            $predictedClass = (string) $classification['prediction']['kelas'];
            $recommendedCommodityId = $predictedClass === 'N' ? null : (int) $commodity['id'];
        } catch (\Throwable $exception) {
            return redirect()->to('/klasifikasi')->withInput()->with('error', $exception->getMessage());
        }

        $this->db->transStart();
        $dataUjiId = (int) $this->dataUjiModel->insert($dataUjiPayload, true);

        $headerPayload = [
            'data_uji_id'              => $dataUjiId,
            'rekomendasi_komoditas'    => $recommendedCommodityId === null ? 'Tidak direkomendasikan' : (string) $commodity['nama'],
            'komoditas_rekomendasi_id' => $recommendedCommodityId,
            'komoditas_target_id'      => (int) $commodity['id'],
            'kelas_hasil'              => $predictedClass,
            'nilai_k'                  => $classification['effective_k'],
        ];

        $this->hasilKlasifikasiModel->insert($headerPayload);
        $hasilId = (int) $this->hasilKlasifikasiModel->getInsertID();

        $this->hasilKlasifikasiDetailModel->insertBatch(
            $this->buildDetailRows($hasilId, (int) $commodity['id'], $predictedClass)
        );

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

        $targetCommodity = null;
        if (! empty($hasil['komoditas_target_id'])) {
            $targetCommodity = $this->db->table('komoditas')->where('id', (int) $hasil['komoditas_target_id'])->get()->getRowArray();
        }

        $mapColor = '#b45309';
        if ((string) ($hasil['kelas_hasil'] ?? '') !== 'N' && $targetCommodity !== null) {
            $mapColor = (string) ($targetCommodity['warna_peta'] ?? $mapColor);
        }

        $detailRows = $this->db->table('hasil_klasifikasi_detail hd')
            ->select('hd.kelas, hd.komoditas_id, k.nama')
            ->join('komoditas k', 'k.id = hd.komoditas_id')
            ->where('hd.hasil_klasifikasi_id', $hasilId)
            ->orderBy('k.urutan_tampil', 'ASC')
            ->orderBy('k.id', 'ASC')
            ->get()
            ->getResultArray();

        $detailMap = [];
        foreach ($detailRows as $detailRow) {
            $detailMap[(int) $detailRow['komoditas_id']] = $detailRow;
        }

        $detailRows = [];
        foreach ($this->commodityCatalog->active() as $commodityRow) {
            $commodityId = (int) $commodityRow['id'];
            $detailRows[] = $detailMap[$commodityId] ?? [
                'komoditas_id' => $commodityId,
                'nama'         => $commodityRow['nama'],
                'kelas'        => $this->randomDummyClass($hasilId, $commodityId, (string) ($hasil['rekomendasi_komoditas'] ?? '')),
            ];
        }

        $trainingData = $this->dataLatihModel
            ->select('data_latih.*, data_latih_label.kelas')
            ->join('data_latih_label', 'data_latih_label.data_latih_id = data_latih.id')
            ->orderBy('data_latih.id', 'ASC')
            ->findAll();

        $normalization = $this->metrics->computeNormalization($dataUji, $trainingData);
        $confusionMatrix = $this->metrics->computeConfusionMatrix($trainingData, (int) ($hasil['nilai_k'] ?? 3));

        return view('component/klasifikasi/result', [
            'title'             => 'Hasil Klasifikasi',
            'hasil'             => $hasil,
            'dataUji'           => $dataUji,
            'targetCommodity'   => $targetCommodity,
            'detailRows'        => $detailRows,
            'mapColor'          => $mapColor,
            'normalization'     => $normalization,
            'confusionMatrix'   => $confusionMatrix,
        ]);
    }

    private function buildDetailRows(int $hasilId, int $targetCommodityId, string $predictedClass): array
    {
        $rows = [];
        foreach ($this->commodityCatalog->active() as $commodity) {
            $commodityId = (int) $commodity['id'];
            $rows[] = [
                'hasil_klasifikasi_id' => $hasilId,
                'komoditas_id'         => $commodityId,
                'kelas'                => $commodityId === $targetCommodityId
                    ? $predictedClass
                    : $this->randomDummyClass($hasilId, $commodityId, $predictedClass),
            ];
        }

        return $rows;
    }

    private function randomDummyClass(int $hasilId, int $commodityId, string $predictedClass): string
    {
        $classes = ['S1', 'S2', 'S3', 'N'];
        $seed = abs(crc32($hasilId . ':' . $commodityId . ':' . $predictedClass));

        return $classes[$seed % count($classes)];
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
            'luas_lahan'         => null,
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
        $komoditasIds = array_map(static fn(array $row): string => (string) $row['id'], $this->commodityCatalog->active());

        return [
            'komoditas_id'       => 'required|in_list[' . implode(',', $komoditasIds ?: ['0']) . ']',
            'latitude'           => 'required|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'longitude'          => 'required|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
            'kemiringan_lereng'  => 'required|decimal|greater_than_equal_to[0]',
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
            'komoditas_id' => [
                'required' => 'Tanaman wajib dipilih.',
                'in_list'  => 'Tanaman tidak valid.',
            ],
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
     * @return array<int, array<string, mixed>>
     */
    private function trainingData(): array
    {
        return $this->dataLatihModel
            ->select('data_latih.*, data_latih_label.kelas')
            ->join('data_latih_label', 'data_latih_label.data_latih_id = data_latih.id')
            ->orderBy('data_latih.id', 'ASC')
            ->findAll();
    }

    private function selectedCommodity(): ?array
    {
        $commodityId = (int) $this->request->getPost('komoditas_id');
        if ($commodityId <= 0) {
            return null;
        }

        return $this->db->table('komoditas')->where('id', $commodityId)->get()->getRowArray();
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
