<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Libraries\SoilTypeCatalog;
use App\Models\DataLatihLabelModel;
use App\Models\DataLatihModel;
use App\Models\DataUjiContohModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;

class DataLatih extends BaseController
{
    private const PER_PAGE = 10;
    private const CLASS_OPTIONS = ['S1', 'S2', 'S3', 'N'];
    private const CSV_HEADER = ['jenis tanaman', 'lereng', 'elevasi ketinggian', 'curah hujan', 'ph tanah', 'jenis tanah', 'kedalaman', 'irigasi', 'kelas'];
    private const CSV_HEADER_LEGACY = ['d', 'lereng', 'elevasi ketinggian', 'curah hujan', 'ph tanah', 'jenis tanah', 'kedalaman', 'irigasi', 'kelas'];
    private const SAMPLE_MARKER = 'jenis tanaman uji';
    private const SOIL_CODE_MAP = [
        '1' => 'tanah lempung',
        '2' => 'tanah kubi',
        '3' => 'tanah liat',
        '4' => 'tanah berpasir',
    ];

    private DataLatihModel $dataLatihModel;
    private DataLatihLabelModel $dataLatihLabelModel;
    private DataUjiContohModel $dataUjiContohModel;
    private CommodityCatalog $commodityCatalog;

    public function __construct()
    {
        $this->dataLatihModel = new DataLatihModel();
        $this->dataLatihLabelModel = new DataLatihLabelModel();
        $this->dataUjiContohModel = new DataUjiContohModel();
        $this->commodityCatalog = new CommodityCatalog();
    }

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page_data_latih') ?? 1));
        $rows = $this->dataLatihModel
            ->select('data_latih.*, komoditas.nama AS nama_komoditas, data_latih_label.kelas')
            ->join('komoditas', 'komoditas.id = data_latih.komoditas_id')
            ->join('data_latih_label', 'data_latih_label.data_latih_id = data_latih.id', 'left')
            ->orderBy('data_latih.id', 'DESC')
            ->paginate(self::PER_PAGE, 'data_latih', $page);

        return view('component/data_latih/index', [
            'title'      => 'Data Latih',
            'dataLatih'  => $rows,
            'pager'      => $this->dataLatihModel->pager,
            'totalData'  => $this->dataLatihModel->countAll(),
            'page'       => $page,
            'perPage'    => self::PER_PAGE,
            'csvHeaders' => self::CSV_HEADER,
        ]);
    }

    public function create()
    {
        return view('component/data_latih/form', [
            'title'             => 'Tambah Data Latih',
            'formTitle'         => 'Tambah Data Latih',
            'formAction'        => site_url('data-latih'),
            'submitLabel'       => 'Simpan Data',
            'dataLatih'         => null,
            'jenisTanahOptions' => SoilTypeCatalog::all(),
            'komoditasOptions'  => $this->commodityCatalog->active(),
            'kelasOptions'      => self::CLASS_OPTIONS,
            'validation'        => service('validation'),
        ]);
    }

    public function store()
    {
        $komoditas = $this->commodityCatalog->active();
        if (! $this->validate($this->validationRules($komoditas), $this->validationMessages())) {
            return redirect()->to('/data-latih/create')->withInput();
        }

        $payload = $this->basePayload();
        $kelas = trim((string) $this->request->getPost('kelas'));

        $this->db->transStart();
        $dataLatihId = (int) $this->dataLatihModel->insert($payload, true);
        if ($dataLatihId <= 0) {
            $this->db->transComplete();

            return redirect()->to('/data-latih/create')->withInput()->with('error', 'Data latih gagal disimpan.');
        }

        $this->saveLabelRow($dataLatihId, $kelas);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to('/data-latih/create')->withInput()->with('error', 'Data latih gagal disimpan.');
        }

        return redirect()->to('/data-latih')->with('success', 'Data latih berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $dataLatih = $this->dataLatihModel
            ->select('data_latih.*, data_latih_label.kelas')
            ->join('data_latih_label', 'data_latih_label.data_latih_id = data_latih.id', 'left')
            ->find($id);

        if ($dataLatih === null) {
            throw PageNotFoundException::forPageNotFound('Data latih tidak ditemukan.');
        }

        return view('component/data_latih/form', [
            'title'             => 'Edit Data Latih',
            'formTitle'         => 'Edit Data Latih',
            'formAction'        => site_url('data-latih/' . $id),
            'submitLabel'       => 'Perbarui Data',
            'dataLatih'         => $dataLatih,
            'jenisTanahOptions' => SoilTypeCatalog::all(),
            'komoditasOptions'  => $this->commodityCatalog->active(),
            'kelasOptions'      => self::CLASS_OPTIONS,
            'validation'        => service('validation'),
        ]);
    }

    public function update(int $id)
    {
        if ($this->dataLatihModel->find($id) === null) {
            throw PageNotFoundException::forPageNotFound('Data latih tidak ditemukan.');
        }

        $komoditas = $this->commodityCatalog->active();
        if (! $this->validate($this->validationRules($komoditas), $this->validationMessages())) {
            return redirect()->to('/data-latih/' . $id . '/edit')->withInput();
        }

        $payload = $this->basePayload();
        $kelas = trim((string) $this->request->getPost('kelas'));

        $this->db->transStart();
        $this->dataLatihModel->update($id, $payload);
        $this->saveLabelRow($id, $kelas);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to('/data-latih/' . $id . '/edit')->withInput()->with('error', 'Data latih gagal diperbarui.');
        }

        return redirect()->to('/data-latih')->with('success', 'Data latih berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if ($this->dataLatihModel->find($id) === null) {
            return redirect()->to('/data-latih')->with('error', 'Data latih tidak ditemukan.');
        }

        $this->dataLatihModel->delete($id);

        return redirect()->to('/data-latih')->with('success', 'Data latih berhasil dihapus.');
    }

    public function import()
    {
        $file = $this->request->getFile('data_latih_csv');

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return redirect()->to('/data-latih')->with('error', 'File CSV wajib dipilih.');
        }

        if (strtolower($file->getExtension()) !== 'csv') {
            return redirect()->to('/data-latih')->with('error', 'Format file harus CSV.');
        }

        $handle = fopen($file->getTempName(), 'rb');
        if ($handle === false) {
            return redirect()->to('/data-latih')->with('error', 'File CSV tidak dapat dibaca.');
        }

        $commodityMap = $this->commodityNameMap();
        [$trainingRows, $sampleRows, $errors] = $this->parseManualCsv($handle, $commodityMap);
        fclose($handle);

        if ($errors !== []) {
            return redirect()->to('/data-latih')
                ->with('error', 'Import CSV dibatalkan karena format manual tidak valid.')
                ->with('import_errors', $errors);
        }

        if ($trainingRows === []) {
            return redirect()->to('/data-latih')->with('error', 'Tidak ada data latih valid yang bisa diimport.');
        }

        $this->db->transStart();
        $this->db->query('DELETE FROM data_latih_label');
        $this->db->query('DELETE FROM data_uji_contoh');
        $this->db->query('DELETE FROM data_latih');

        foreach ($trainingRows as $row) {
            $dataLatihId = (int) $this->dataLatihModel->insert($row['payload'], true);
            if ($dataLatihId <= 0) {
                $this->db->transRollback();

                return redirect()->to('/data-latih')->with('error', 'Import data latih gagal disimpan ke database.');
            }

            $this->dataLatihLabelModel->insert([
                'data_latih_id' => $dataLatihId,
                'kelas'         => $row['kelas'],
            ]);
        }

        if ($sampleRows !== []) {
            $this->dataUjiContohModel->insertBatch($sampleRows);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to('/data-latih')->with('error', 'Import data latih gagal disimpan ke database.');
        }

        return redirect()->to('/data-latih')->with(
            'success',
            count($trainingRows) . ' data latih dan ' . count($sampleRows) . ' data uji contoh berhasil diimport.'
        );
    }

    public function export()
    {
        $rows = $this->dataLatihModel
            ->select('data_latih.*, komoditas.nama AS nama_komoditas, data_latih_label.kelas')
            ->join('komoditas', 'komoditas.id = data_latih.komoditas_id')
            ->join('data_latih_label', 'data_latih_label.data_latih_id = data_latih.id', 'left')
            ->orderBy('data_latih.id', 'ASC')
            ->findAll();

        $lines = [implode(',', self::CSV_HEADER)];
        foreach ($rows as $row) {
            $lines[] = $this->toCsvLine([
                strtolower((string) $row['nama_komoditas']),
                $row['kemiringan_lereng'],
                $row['ketinggian_tempat'],
                $row['curah_hujan'],
                $row['ph_tanah'],
                $row['jenis_tanah'],
                $row['kedalaman_tanah'],
                $row['irigasi'],
                $row['kelas'] ?? '',
            ]);
        }

        $filename = 'data-latih-manual-' . date('Ymd-His') . '.csv';

        return $this->response
            ->setStatusCode(200)
            ->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(implode("\n", $lines));
    }

    private function basePayload(): array
    {
        return [
            'kemiringan_lereng' => $this->request->getPost('kemiringan_lereng'),
            'luas_lahan'        => null,
            'ph_tanah'          => $this->request->getPost('ph_tanah'),
            'jenis_tanah'       => $this->request->getPost('jenis_tanah'),
            'ketinggian_tempat' => $this->request->getPost('ketinggian_tempat'),
            'kedalaman_tanah'   => $this->request->getPost('kedalaman_tanah'),
            'curah_hujan'       => $this->request->getPost('curah_hujan'),
            'irigasi'           => $this->request->getPost('irigasi'),
            'komoditas_id'      => (int) $this->request->getPost('komoditas_id'),
            'latitude'          => null,
            'longitude'         => null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     */
    private function validationRules(array $komoditas): array
    {
        $komoditasIds = array_map(static fn(array $row): string => (string) $row['id'], $komoditas);

        return [
            'kemiringan_lereng' => 'required|decimal|greater_than_equal_to[0]',
            'ph_tanah'          => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[14]',
            'jenis_tanah'       => 'required|in_list[' . implode(',', SoilTypeCatalog::all()) . ']',
            'ketinggian_tempat' => 'required|integer|greater_than_equal_to[0]',
            'kedalaman_tanah'   => 'required|integer|greater_than[0]',
            'curah_hujan'       => 'required|integer|greater_than[0]',
            'irigasi'           => 'required|integer|greater_than_equal_to[0]',
            'komoditas_id'      => 'required|in_list[' . implode(',', $komoditasIds ?: ['0']) . ']',
            'kelas'             => 'required|in_list[' . implode(',', self::CLASS_OPTIONS) . ']',
        ];
    }

    private function validationMessages(): array
    {
        return [
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
            'komoditas_id' => [
                'required' => 'Jenis tanaman wajib dipilih.',
                'in_list'  => 'Jenis tanaman tidak valid.',
            ],
            'kelas' => [
                'required' => 'Kelas wajib dipilih.',
                'in_list'  => 'Kelas tidak valid.',
            ],
        ];
    }

    /**
     * @param array<string, int> $commodityMap
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: list<string>}
     */
    private function parseManualCsv($handle, array $commodityMap): array
    {
        $trainingRows = [];
        $sampleRows = [];
        $errors = [];

        $header = fgetcsv($handle, 0, ',');
        if ($header === false) {
            return [[], [], ['File CSV kosong.']];
        }

        $normalizedHeader = array_map([$this, 'normalizeHeaderValue'], $header);
        if ($normalizedHeader !== self::CSV_HEADER && $normalizedHeader !== self::CSV_HEADER_LEGACY) {
            return [[], [], ['Header CSV tidak cocok dengan format manual data lahan.csv.']];
        }

        $isSampleSection = false;
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $first = mb_strtolower(trim((string) ($row[0] ?? '')));
            if ($first === self::SAMPLE_MARKER) {
                $isSampleSection = true;
                continue;
            }

            $baseCells = array_slice($row, 0, 9);
            if (count($baseCells) < 9) {
                $errors[] = 'Baris ' . $rowNumber . ': kolom data inti tidak lengkap.';
                continue;
            }

            $parsed = $this->parseCoreRow($baseCells, $commodityMap, $rowNumber);
            if ($parsed === null) {
                $errors[] = 'Baris ' . $rowNumber . ': tanaman, jenis tanah, atau kelas tidak valid.';
                continue;
            }

            if ($isSampleSection) {
                $sampleRows[] = $parsed['sample'];
            } else {
                $trainingRows[] = $parsed['training'];
            }
        }

        return [$trainingRows, $sampleRows, $errors];
    }

    /**
     * @param array<int, string> $cells
     * @param array<string, int> $commodityMap
     * @return array<string, array<string, mixed>>|null
     */
    private function parseCoreRow(array $cells, array $commodityMap, int $rowNumber): ?array
    {
        $commodityName = mb_strtolower(trim((string) ($cells[0] ?? '')));
        $commodityId = $commodityMap[$commodityName] ?? null;
        $soil = $this->normalizeSoilType((string) ($cells[5] ?? ''));
        $kelas = strtoupper(trim((string) ($cells[8] ?? '')));

        if ($commodityId === null || $soil === '' || ! in_array($kelas, self::CLASS_OPTIONS, true)) {
            return null;
        }

        $payload = [
            'kemiringan_lereng' => $this->normalizeDecimalValue((string) ($cells[1] ?? '')),
            'luas_lahan'        => null,
            'ph_tanah'          => $this->normalizeDecimalValue((string) ($cells[4] ?? '')),
            'jenis_tanah'       => $soil,
            'ketinggian_tempat' => $this->normalizeIntegerValue((string) ($cells[2] ?? '')),
            'kedalaman_tanah'   => $this->normalizeIntegerValue((string) ($cells[6] ?? '')),
            'curah_hujan'       => $this->normalizeIntegerValue((string) ($cells[3] ?? '')),
            'irigasi'           => $this->normalizeIntegerValue((string) ($cells[7] ?? '')),
            'komoditas_id'      => $commodityId,
            'latitude'          => null,
            'longitude'         => null,
        ];

        $validation = Services::validation();
        $validation->setRules([
            'kemiringan_lereng' => 'required|decimal|greater_than_equal_to[0]',
            'ph_tanah'          => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[14]',
            'jenis_tanah'       => 'required|in_list[' . implode(',', SoilTypeCatalog::all()) . ']',
            'ketinggian_tempat' => 'required|integer|greater_than_equal_to[0]',
            'kedalaman_tanah'   => 'required|integer|greater_than[0]',
            'curah_hujan'       => 'required|integer|greater_than[0]',
            'irigasi'           => 'required|integer|greater_than_equal_to[0]',
            'komoditas_id'      => 'required|integer',
        ]);

        if (! $validation->run($payload)) {
            return null;
        }

        return [
            'training' => [
                'payload' => $payload,
                'kelas'   => $kelas,
            ],
            'sample' => [
                'komoditas_id'      => $commodityId,
                'kemiringan_lereng' => $payload['kemiringan_lereng'],
                'ph_tanah'          => $payload['ph_tanah'],
                'jenis_tanah'       => $payload['jenis_tanah'],
                'ketinggian_tempat' => $payload['ketinggian_tempat'],
                'kedalaman_tanah'   => $payload['kedalaman_tanah'],
                'curah_hujan'       => $payload['curah_hujan'],
                'irigasi'           => $payload['irigasi'],
                'kelas_ekspektasi'  => $kelas,
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function commodityNameMap(): array
    {
        $map = [];
        foreach ($this->commodityCatalog->all() as $commodity) {
            $name = mb_strtolower(trim((string) $commodity['nama']));
            $map[$name] = (int) $commodity['id'];
            if ($name === 'cabe') {
                $map['cabai'] = (int) $commodity['id'];
            }
        }

        return $map;
    }

    private function saveLabelRow(int $dataLatihId, string $kelas): void
    {
        $existing = $this->dataLatihLabelModel->where('data_latih_id', $dataLatihId)->first();
        $payload = [
            'data_latih_id' => $dataLatihId,
            'kelas'         => $kelas,
        ];

        if ($existing === null) {
            $this->dataLatihLabelModel->insert($payload);

            return;
        }

        $this->dataLatihLabelModel->update((int) $existing['id'], $payload);
    }

    private function normalizeHeaderValue(string $value): string
    {
        return strtolower(trim(str_replace("\xEF\xBB\xBF", '', $value)));
    }

    private function normalizeSoilType(string $value): string
    {
        $normalized = trim($value);
        if ($normalized !== '' && isset(self::SOIL_CODE_MAP[$normalized])) {
            return self::SOIL_CODE_MAP[$normalized];
        }

        $normalizedInput = mb_strtolower($normalized);
        foreach (SoilTypeCatalog::all() as $option) {
            if ($normalizedInput === mb_strtolower($option)) {
                return $option;
            }
        }

        return '';
    }

    private function normalizeDecimalValue(string $value): string
    {
        $normalized = trim(str_replace(',', '.', str_replace('%', '', $value)));
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';
        if ($normalized === '') {
            return '';
        }

        $isNegative = str_starts_with($normalized, '-');
        $body = $isNegative ? substr($normalized, 1) : $normalized;
        $parts = explode('.', $body);

        if (count($parts) <= 1) {
            return $isNegative ? '-' . $body : $body;
        }

        return ($isNegative ? '-' : '') . array_shift($parts) . '.' . implode('', $parts);
    }

    private function normalizeIntegerValue(string $value): string
    {
        $normalized = preg_replace('/[^0-9\-]/', '', trim($value)) ?? '';

        return ($normalized === '' || $normalized === '-') ? '' : $normalized;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function toCsvLine(array $values): string
    {
        return implode(',', array_map(static function (mixed $value): string {
            $string = (string) $value;
            $escaped = str_replace('"', '""', $string);

            if (str_contains($escaped, ',') || str_contains($escaped, '"') || str_contains($escaped, "\n")) {
                return '"' . $escaped . '"';
            }

            return $escaped;
        }, $values));
    }
}
