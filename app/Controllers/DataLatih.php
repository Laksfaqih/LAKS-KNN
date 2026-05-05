<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Libraries\SoilTypeCatalog;
use App\Models\DataLatihModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;

class DataLatih extends BaseController
{
    private const PER_PAGE = 10;

    private DataLatihModel $dataLatihModel;
    private CommodityCatalog $commodityCatalog;

    public function __construct()
    {
        $this->dataLatihModel = new DataLatihModel();
        $this->commodityCatalog = new CommodityCatalog();
    }

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page_data_latih') ?? 1));
        $komoditas = $this->commodityCatalog->active();
        $rows = $this->dataLatihModel
            ->select('data_latih.*, komoditas.nama AS nama_komoditas')
            ->join('komoditas', 'komoditas.id = data_latih.komoditas_id')
            ->orderBy('data_latih.id', 'DESC')
            ->paginate(self::PER_PAGE, 'data_latih', $page);

        return view('component/data_latih/index', [
            'title'       => 'Data Latih',
            'dataLatih'   => $rows,
            'pager'       => $this->dataLatihModel->pager,
            'totalData'   => $this->dataLatihModel->countAll(),
            'page'        => $page,
            'perPage'     => self::PER_PAGE,
            'komoditas'   => $komoditas,
            'csvHeaders'  => $this->csvHeaders(),
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
            'validation'        => service('validation'),
        ]);
    }

    public function store()
    {
        $komoditas = $this->commodityCatalog->active();
        if (! $this->validate($this->validationRules($komoditas), $this->validationMessages())) {
            return redirect()->to('/data-latih/create')->withInput();
        }

        $this->dataLatihModel->insert($this->basePayload());

        return redirect()->to('/data-latih')->with('success', 'Data latih berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $dataLatih = $this->dataLatihModel->find($id);
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

        $this->dataLatihModel->update($id, $this->basePayload());

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

        $komoditas = $this->commodityCatalog->active();
        $expectedHeaders = $this->csvHeaders();
        $handle = fopen($file->getTempName(), 'rb');
        if ($handle === false) {
            return redirect()->to('/data-latih')->with('error', 'File CSV tidak dapat dibaca.');
        }

        $headerLine = fgets($handle);
        if ($headerLine === false) {
            fclose($handle);

            return redirect()->to('/data-latih')->with('error', 'File CSV kosong.');
        }

        $delimiter = $this->detectCsvDelimiter($headerLine);
        $header = str_getcsv($headerLine, $delimiter);
        $normalizedHeader = array_map([$this, 'normalizeHeaderValue'], $header);
        $missingHeaders = array_values(array_diff($expectedHeaders, $normalizedHeader));
        $unknownHeaders = array_values(array_diff($normalizedHeader, $expectedHeaders));
        if ($missingHeaders !== [] || $unknownHeaders !== []) {
            fclose($handle);

            $details = ['Gunakan header: ' . implode(', ', $expectedHeaders)];
            if ($missingHeaders !== []) {
                $details[] = 'Header kurang: ' . implode(', ', $missingHeaders);
            }
            if ($unknownHeaders !== []) {
                $details[] = 'Header tidak dikenali: ' . implode(', ', $unknownHeaders);
            }

            return redirect()->to('/data-latih')
                ->with('error', 'Header CSV tidak sesuai format yang dibutuhkan.')
                ->with('import_errors', $details);
        }

        $records = [];
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if ($this->isRowEmpty($row)) {
                continue;
            }

            if (count($row) !== count($expectedHeaders)) {
                $errors[] = 'Baris ' . $rowNumber . ': jumlah kolom tidak sesuai header.';
                continue;
            }

            $payload = array_combine($normalizedHeader, array_map('trim', $row));
            if ($payload === false) {
                $errors[] = 'Baris ' . $rowNumber . ': gagal membaca data.';
                continue;
            }

            $payload = $this->normalizeImportPayload($payload, $komoditas);

            $validationErrors = $this->validateImportRow($payload, $komoditas);
            if ($validationErrors !== []) {
                $errors[] = 'Baris ' . $rowNumber . ': ' . implode(' ', $validationErrors);
                continue;
            }

            $records[] = $this->basePayloadFromArray($payload, $komoditas);
        }

        fclose($handle);

        if ($records === [] && $errors === []) {
            return redirect()->to('/data-latih')->with('error', 'File CSV tidak memiliki data untuk diimpor.');
        }

        if ($errors !== []) {
            return redirect()->to('/data-latih')
                ->with('error', 'Import CSV dibatalkan karena ada data yang tidak valid.')
                ->with('import_errors', $errors);
        }

        foreach ($records as $record) {
            $this->dataLatihModel->insert($record);
        }

        return redirect()->to('/data-latih')->with('success', count($records) . ' data latih berhasil diimpor.');
    }

    public function export()
    {
        $commodityMap = $this->commodityCatalog->idMap(false);

        $rows = $this->dataLatihModel->orderBy('id', 'ASC')->findAll();
        $lines = [implode(',', $this->csvHeaders())];

        foreach ($rows as $row) {
            $lines[] = $this->toCsvLine([
                $row['kemiringan_lereng'],
                $row['luas_lahan'],
                $row['ph_tanah'],
                $row['jenis_tanah'],
                $row['ketinggian_tempat'],
                $row['kedalaman_tanah'],
                $row['curah_hujan'],
                $row['irigasi'],
                $commodityMap[(int) $row['komoditas_id']]['nama'] ?? '',
                $row['latitude'],
                $row['longitude'],
            ]);
        }

        $filename = 'data-latih-' . date('Ymd-His') . '.csv';

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
            'luas_lahan'        => $this->request->getPost('luas_lahan'),
            'ph_tanah'          => $this->request->getPost('ph_tanah'),
            'jenis_tanah'       => $this->request->getPost('jenis_tanah'),
            'ketinggian_tempat' => $this->request->getPost('ketinggian_tempat'),
            'kedalaman_tanah'   => $this->request->getPost('kedalaman_tanah'),
            'curah_hujan'       => $this->request->getPost('curah_hujan'),
            'irigasi'           => $this->request->getPost('irigasi'),
            'komoditas_id'      => (int) $this->request->getPost('komoditas_id'),
            'latitude'          => $this->request->getPost('latitude'),
            'longitude'         => $this->request->getPost('longitude'),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     */
    private function basePayloadFromArray(array $payload, array $komoditas): array
    {
        $nameMap = [];
        foreach ($komoditas as $commodity) {
            $nameMap[mb_strtolower(trim((string) $commodity['nama']))] = (int) $commodity['id'];
        }

        return [
            'kemiringan_lereng' => $payload['kemiringan_lereng'],
            'luas_lahan'        => $payload['luas_lahan'],
            'ph_tanah'          => $payload['ph_tanah'],
            'jenis_tanah'       => $payload['jenis_tanah'],
            'ketinggian_tempat' => $payload['ketinggian_tempat'],
            'kedalaman_tanah'   => $payload['kedalaman_tanah'],
            'curah_hujan'       => $payload['curah_hujan'],
            'irigasi'           => $payload['irigasi'],
            'komoditas_id'      => $nameMap[mb_strtolower(trim((string) $payload['jenis_tanaman']))] ?? 0,
            'latitude'          => $payload['latitude'],
            'longitude'         => $payload['longitude'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     */
    private function validationRules(array $komoditas, bool $forImport = false): array
    {
        $komoditasIds = array_map(static fn(array $row): string => (string) $row['id'], $komoditas);
        $komoditasNames = array_map(static fn(array $row): string => (string) $row['nama'], $komoditas);

        $rules = [
            'kemiringan_lereng' => 'required|decimal|greater_than_equal_to[0]',
            'luas_lahan'        => 'required|decimal|greater_than[0]',
            'ph_tanah'          => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[14]',
            'jenis_tanah'       => 'required|in_list[' . implode(',', SoilTypeCatalog::all()) . ']',
            'ketinggian_tempat' => 'required|integer|greater_than_equal_to[0]',
            'kedalaman_tanah'   => 'required|integer|greater_than[0]',
            'curah_hujan'       => 'required|integer|greater_than[0]',
            'irigasi'           => 'required|integer|greater_than_equal_to[0]',
            'latitude'          => 'required|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'longitude'         => 'required|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
        ];

        if ($forImport) {
            $rules['jenis_tanaman'] = 'required|in_list[' . implode(',', $komoditasNames ?: ['']) . ']';
        } else {
            $rules['komoditas_id'] = 'required|in_list[' . implode(',', $komoditasIds ?: ['0']) . ']';
        }

        return $rules;
    }

    private function validationMessages(): array
    {
        return [
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
            'komoditas_id' => [
                'required' => 'Jenis tanaman wajib dipilih.',
                'in_list'  => 'Jenis tanaman tidak valid.',
            ],
            'jenis_tanaman' => [
                'required' => 'Jenis tanaman wajib dipilih.',
                'in_list' => 'Jenis tanaman pada CSV harus sama dengan nama komoditas aktif.',
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
        ];
    }

    private function csvHeaders(): array
    {
        return [
            'kemiringan_lereng',
            'luas_lahan',
            'ph_tanah',
            'jenis_tanah',
            'ketinggian_tempat',
            'kedalaman_tanah',
            'curah_hujan',
            'irigasi',
            'jenis_tanaman',
            'latitude',
            'longitude',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     * @return array<string, string>
     */
    private function validateImportRow(array $payload, array $komoditas): array
    {
        $validation = Services::validation();
        $validation->setRules($this->validationRules($komoditas, true), $this->validationMessages());
        $validation->run($payload);

        return $validation->getErrors();
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     * @return array<string, string>
     */
    private function normalizeImportPayload(array $payload, array $komoditas): array
    {
        if (isset($payload['jenis_tanah'])) {
            $payload['jenis_tanah'] = $this->normalizeSoilType((string) $payload['jenis_tanah']);
        }

        if (isset($payload['jenis_tanaman'])) {
            $payload['jenis_tanaman'] = $this->normalizeCommodityName((string) $payload['jenis_tanaman'], $komoditas);
        }

        foreach (['kemiringan_lereng', 'luas_lahan', 'ph_tanah', 'latitude', 'longitude'] as $decimalField) {
            if (isset($payload[$decimalField])) {
                $payload[$decimalField] = $this->normalizeDecimalValue((string) $payload[$decimalField]);
            }
        }

        if (isset($payload['latitude'])) {
            $payload['latitude'] = $this->normalizeCoordinateValue((string) $payload['latitude'], 90.0);
        }

        if (isset($payload['longitude'])) {
            $payload['longitude'] = $this->normalizeCoordinateValue((string) $payload['longitude'], 180.0);
        }

        foreach (['ketinggian_tempat', 'kedalaman_tanah', 'curah_hujan', 'irigasi'] as $integerField) {
            if (isset($payload[$integerField])) {
                $payload[$integerField] = $this->normalizeIntegerValue((string) $payload[$integerField]);
            }
        }

        return $payload;
    }

    private function normalizeSoilType(string $value): string
    {
        $normalizedInput = mb_strtolower(trim($value));

        foreach (SoilTypeCatalog::all() as $option) {
            if ($normalizedInput === mb_strtolower($option)) {
                return $option;
            }
        }

        return trim($value);
    }

    /**
     * @param array<int, array<string, mixed>> $komoditas
     */
    private function normalizeCommodityName(string $value, array $komoditas): string
    {
        $normalizedInput = mb_strtolower(trim($value));

        foreach ($komoditas as $commodity) {
            $name = trim((string) $commodity['nama']);
            if ($normalizedInput === mb_strtolower($name)) {
                return $name;
            }
        }

        return trim($value);
    }

    private function normalizeHeaderValue(string $value): string
    {
        $normalized = strtolower(trim(str_replace("\xEF\xBB\xBF", '', $value)));

        return match ($normalized) {
            // Backward compatibility typo from older CSV templates
            'kemiringan_lereng' => 'kemiringan_lereng',
            'longtitude' => 'longitude',
            default => $normalized,
        };
    }

    private function normalizeDecimalValue(string $value): string
    {
        $normalized = trim(str_replace(',', '.', $value));
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

        $rebuilt = array_shift($parts) . '.' . implode('', $parts);

        return $isNegative ? '-' . $rebuilt : $rebuilt;
    }

    private function normalizeIntegerValue(string $value): string
    {
        $normalized = preg_replace('/[^0-9\-]/', '', trim($value)) ?? '';
        if ($normalized === '' || $normalized === '-') {
            return '';
        }

        return $normalized;
    }

    private function normalizeCoordinateValue(string $value, float $maxAbs): string
    {
        $normalized = $this->normalizeDecimalValue($value);
        if ($normalized === '' || ! is_numeric($normalized)) {
            return $normalized;
        }

        $numeric = (float) $normalized;
        if (abs($numeric) <= $maxAbs) {
            return $normalized;
        }

        $candidates = [];
        foreach ([10, 100, 1000, 10000] as $divisor) {
            $candidate = $numeric / $divisor;
            if (abs($candidate) <= $maxAbs) {
                $candidates[] = $candidate;
            }
        }

        if ($candidates === []) {
            return $normalized;
        }

        if ($maxAbs === 90.0) {
            foreach ($candidates as $candidate) {
                if (abs($candidate) <= 20.0) {
                    return rtrim(rtrim(sprintf('%.10F', $candidate), '0'), '.');
                }
            }
        }

        if ($maxAbs === 180.0) {
            foreach ($candidates as $candidate) {
                if (abs($candidate) >= 90.0) {
                    return rtrim(rtrim(sprintf('%.10F', $candidate), '0'), '.');
                }
            }
        }

        return rtrim(rtrim(sprintf('%.10F', $candidates[0]), '0'), '.');
    }

    private function detectCsvDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];
        $bestDelimiter = ',';
        $bestCount = -1;

        foreach ($candidates as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
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
