<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Models\DataLatihKomoditasModel;
use App\Models\DataLatihModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Services;

class DataLatih extends BaseController
{
    private const PER_PAGE = 10;
    private const KELAS_OPTIONS = ['S1', 'S2', 'S3', 'N'];
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
    private CommodityCatalog $commodityCatalog;

    public function __construct()
    {
        $this->dataLatihModel = new DataLatihModel();
        $this->dataLatihKomoditasModel = new DataLatihKomoditasModel();
        $this->commodityCatalog = new CommodityCatalog();
    }

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page_data_latih') ?? 1));
        $komoditas = $this->commodityCatalog->active();
        $rows = $this->dataLatihModel->ordered()->paginate(self::PER_PAGE, 'data_latih', $page);

        return view('component/data_latih/index', [
            'title'       => 'Data Latih',
            'dataLatih'   => $this->attachCommodityLabels($rows, $komoditas),
            'pager'       => $this->dataLatihModel->pager,
            'totalData'   => $this->dataLatihModel->countAll(),
            'page'        => $page,
            'perPage'     => self::PER_PAGE,
            'komoditas'   => $komoditas,
            'csvHeaders'  => $this->csvHeaders($komoditas),
        ]);
    }

    public function create()
    {
        $komoditas = $this->commodityCatalog->active();

        return view('component/data_latih/form', [
            'title'             => 'Tambah Data Latih',
            'formTitle'         => 'Tambah Data Latih',
            'formAction'        => site_url('data-latih'),
            'submitLabel'       => 'Simpan Data',
            'dataLatih'         => null,
            'jenisTanahOptions' => self::JENIS_TANAH_OPTIONS,
            'kelasOptions'      => self::KELAS_OPTIONS,
            'komoditas'         => $komoditas,
            'validation'        => service('validation'),
        ]);
    }

    public function store()
    {
        $komoditas = $this->commodityCatalog->active();
        if (! $this->validate($this->validationRules($komoditas), $this->validationMessages($komoditas))) {
            return redirect()->to('/data-latih/create')->withInput();
        }

        $this->persistDataLatih($this->basePayload(), $this->commodityPayload($komoditas));

        return redirect()->to('/data-latih')->with('success', 'Data latih berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $dataLatih = $this->dataLatihModel->find($id);
        if ($dataLatih === null) {
            throw PageNotFoundException::forPageNotFound('Data latih tidak ditemukan.');
        }

        $komoditas = $this->commodityCatalog->active();
        $dataLatih['commodity_labels'] = $this->labelsByDataLatihId([$id])[$id] ?? [];

        return view('component/data_latih/form', [
            'title'             => 'Edit Data Latih',
            'formTitle'         => 'Edit Data Latih',
            'formAction'        => site_url('data-latih/' . $id),
            'submitLabel'       => 'Perbarui Data',
            'dataLatih'         => $dataLatih,
            'jenisTanahOptions' => self::JENIS_TANAH_OPTIONS,
            'kelasOptions'      => self::KELAS_OPTIONS,
            'komoditas'         => $komoditas,
            'validation'        => service('validation'),
        ]);
    }

    public function update(int $id)
    {
        if ($this->dataLatihModel->find($id) === null) {
            throw PageNotFoundException::forPageNotFound('Data latih tidak ditemukan.');
        }

        $komoditas = $this->commodityCatalog->active();
        if (! $this->validate($this->validationRules($komoditas), $this->validationMessages($komoditas))) {
            return redirect()->to('/data-latih/' . $id . '/edit')->withInput();
        }

        $this->persistDataLatih($this->basePayload(), $this->commodityPayload($komoditas), $id);

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
        $expectedHeaders = $this->csvHeaders($komoditas);
        $handle = fopen($file->getTempName(), 'rb');
        if ($handle === false) {
            return redirect()->to('/data-latih')->with('error', 'File CSV tidak dapat dibaca.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return redirect()->to('/data-latih')->with('error', 'File CSV kosong.');
        }

        $normalizedHeader = array_map([$this, 'normalizeHeaderValue'], $header);
        if ($normalizedHeader !== $expectedHeaders) {
            fclose($handle);

            return redirect()->to('/data-latih')
                ->with('error', 'Header CSV tidak sesuai format yang dibutuhkan.')
                ->with('import_errors', ['Gunakan urutan header: ' . implode(', ', $expectedHeaders)]);
        }

        $records = [];
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isRowEmpty($row)) {
                continue;
            }

            if (count($row) !== count($expectedHeaders)) {
                $errors[] = 'Baris ' . $rowNumber . ': jumlah kolom tidak sesuai header.';
                continue;
            }

            $payload = array_combine($expectedHeaders, array_map('trim', $row));
            if ($payload === false) {
                $errors[] = 'Baris ' . $rowNumber . ': gagal membaca data.';
                continue;
            }

            $validationErrors = $this->validateImportRow($payload, $komoditas);
            if ($validationErrors !== []) {
                $errors[] = 'Baris ' . $rowNumber . ': ' . implode(' ', $validationErrors);
                continue;
            }

            $records[] = [
                'base'      => $this->basePayloadFromArray($payload),
                'commodity' => $this->commodityPayloadFromArray($payload, $komoditas),
            ];
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
            $this->persistDataLatih($record['base'], $record['commodity']);
        }

        return redirect()->to('/data-latih')->with('success', count($records) . ' data latih berhasil diimpor.');
    }

    public function export()
    {
        $komoditas = $this->commodityCatalog->active();
        $headers = $this->csvHeaders($komoditas);
        $rows = $this->attachCommodityLabels($this->dataLatihModel->orderBy('id', 'ASC')->findAll(), $komoditas);

        $lines = [implode(',', $headers)];

        foreach ($rows as $row) {
            $values = [
                $row['kemiringan_lereng'],
                $row['luas_lahan'],
                $row['ph_tanah'],
                $row['jenis_tanah'],
                $row['ketinggian_tempat'],
                $row['kedalaman_tanah'],
                $row['curah_hujan'],
            ];

            foreach ($komoditas as $commodity) {
                $values[] = $row['commodity_labels'][(int) $commodity['id']] ?? '';
            }

            $lines[] = $this->toCsvLine($values);
        }

        $filename = 'data-latih-' . date('Ymd-His') . '.csv';

        return $this->response
            ->setStatusCode(200)
            ->setContentType('text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(implode("\n", $lines));
    }

    private function persistDataLatih(array $basePayload, array $commodityPayload, ?int $id = null): void
    {
        $this->db->transStart();

        if ($id === null) {
            $id = (int) $this->dataLatihModel->insert($this->legacyAwarePayload($basePayload, $commodityPayload), true);
        } else {
            $this->dataLatihModel->update($id, $this->legacyAwarePayload($basePayload, $commodityPayload));
            $this->dataLatihKomoditasModel->where('data_latih_id', $id)->delete();
        }

        foreach ($commodityPayload as $komoditasId => $kelas) {
            $this->dataLatihKomoditasModel->insert([
                'data_latih_id' => $id,
                'komoditas_id'  => $komoditasId,
                'kelas'         => $kelas,
            ]);
        }

        $this->db->transComplete();
    }

    private function legacyAwarePayload(array $basePayload, array $commodityPayload): array
    {
        $slugMap = $this->commodityCatalog->slugMap();

        return $basePayload + [
            'kelas_tebu'   => $commodityPayload[$slugMap['tebu']['id'] ?? 0] ?? 'N',
            'kelas_jagung' => $commodityPayload[$slugMap['jagung']['id'] ?? 0] ?? 'N',
            'kelas_cabai'  => $commodityPayload[$slugMap['cabe']['id'] ?? 0] ?? 'N',
            'kelas_padi'   => $commodityPayload[$slugMap['padi']['id'] ?? 0] ?? 'N',
        ];
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
        ];
    }

    private function commodityPayload(array $komoditas): array
    {
        $payload = [];
        foreach ($komoditas as $commodity) {
            $payload[(int) $commodity['id']] = (string) $this->request->getPost('kelas_' . $commodity['slug']);
        }

        return $payload;
    }

    private function basePayloadFromArray(array $payload): array
    {
        return [
            'kemiringan_lereng' => $payload['kemiringan_lereng'],
            'luas_lahan'        => $payload['luas_lahan'],
            'ph_tanah'          => $payload['ph_tanah'],
            'jenis_tanah'       => $payload['jenis_tanah'],
            'ketinggian_tempat' => $payload['ketinggian_tempat'],
            'kedalaman_tanah'   => $payload['kedalaman_tanah'],
            'curah_hujan'       => $payload['curah_hujan'],
        ];
    }

    private function commodityPayloadFromArray(array $payload, array $komoditas): array
    {
        $rows = [];
        foreach ($komoditas as $commodity) {
            $rows[(int) $commodity['id']] = $payload['kelas_' . $commodity['slug']];
        }

        return $rows;
    }

    private function validationRules(array $komoditas): array
    {
        $rules = [
            'kemiringan_lereng' => 'required|decimal|greater_than_equal_to[0]',
            'luas_lahan'        => 'required|decimal|greater_than[0]',
            'ph_tanah'          => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[14]',
            'jenis_tanah'       => 'required|in_list[' . implode(',', self::JENIS_TANAH_OPTIONS) . ']',
            'ketinggian_tempat' => 'required|integer|greater_than_equal_to[0]',
            'kedalaman_tanah'   => 'required|integer|greater_than[0]',
            'curah_hujan'       => 'required|integer|greater_than[0]',
        ];

        foreach ($komoditas as $commodity) {
            $rules['kelas_' . $commodity['slug']] = 'required|in_list[' . implode(',', self::KELAS_OPTIONS) . ']';
        }

        return $rules;
    }

    private function validationMessages(array $komoditas): array
    {
        $messages = [
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
        ];

        foreach ($komoditas as $commodity) {
            $messages['kelas_' . $commodity['slug']] = [
                'required' => 'Kelas ' . $commodity['nama'] . ' wajib dipilih.',
                'in_list'  => 'Kelas ' . $commodity['nama'] . ' tidak valid.',
            ];
        }

        return $messages;
    }

    private function csvHeaders(array $komoditas): array
    {
        $headers = [
            'kemiringan_lereng',
            'luas_lahan',
            'ph_tanah',
            'jenis_tanah',
            'ketinggian_tempat',
            'kedalaman_tanah',
            'curah_hujan',
        ];

        foreach ($komoditas as $commodity) {
            $headers[] = 'kelas_' . $commodity['slug'];
        }

        return $headers;
    }

    private function attachCommodityLabels(array $rows, array $komoditas): array
    {
        $labelsById = $this->labelsByDataLatihId(array_map(static fn(array $row): int => (int) $row['id'], $rows));

        foreach ($rows as &$row) {
            $labels = $labelsById[(int) $row['id']] ?? [];
            foreach ($komoditas as $commodity) {
                $commodityId = (int) $commodity['id'];
                $labels[$commodityId] = $labels[$commodityId] ?? 'N';
            }
            $row['commodity_labels'] = $labels;
        }

        return $rows;
    }

    private function labelsByDataLatihId(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->dataLatihKomoditasModel
            ->whereIn('data_latih_id', $ids)
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['data_latih_id']][(int) $row['komoditas_id']] = (string) $row['kelas'];
        }

        return $grouped;
    }

    private function validateImportRow(array $payload, array $komoditas): array
    {
        $validation = Services::validation();
        $validation->setRules($this->validationRules($komoditas), $this->validationMessages($komoditas));
        $validation->run($payload);

        return $validation->getErrors();
    }

    private function normalizeHeaderValue(string $value): string
    {
        return strtolower(trim(str_replace("\xEF\xBB\xBF", '', $value)));
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
