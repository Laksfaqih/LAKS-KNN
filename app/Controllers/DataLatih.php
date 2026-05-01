<?php

namespace App\Controllers;

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
    private const CSV_HEADERS = [
        'kemiringan_lereng',
        'luas_lahan',
        'ph_tanah',
        'jenis_tanah',
        'ketinggian_tempat',
        'kedalaman_tanah',
        'curah_hujan',
        'kelas_tebu',
        'kelas_cabai',
        'kelas_jagung',
        'kelas_padi',
    ];

    private DataLatihModel $dataLatihModel;

    public function __construct()
    {
        $this->dataLatihModel = new DataLatihModel();
    }

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page_data_latih') ?? 1));

        $data = [
            'title'     => 'Data Latih',
            'dataLatih' => $this->dataLatihModel->orderBy('id', 'DESC')->paginate(self::PER_PAGE, 'data_latih', $page),
            'pager'     => $this->dataLatihModel->pager,
            'totalData' => $this->dataLatihModel->countAll(),
            'page'      => $page,
            'perPage'   => self::PER_PAGE,
            'csvHeaders' => self::CSV_HEADERS,
        ];

        return view('component/data_latih/index', $data);
    }

    public function create()
    {
        return view('component/data_latih/form', [
            'title'             => 'Tambah Data Latih',
            'formTitle'         => 'Tambah Data Latih',
            'formAction'        => site_url('data-latih'),
            'submitLabel'       => 'Simpan Data',
            'dataLatih'         => null,
            'jenisTanahOptions' => self::JENIS_TANAH_OPTIONS,
            'kelasOptions'      => self::KELAS_OPTIONS,
            'validation'        => service('validation'),
        ]);
    }

    public function store()
    {
        if (! $this->validate($this->validationRules(), $this->validationMessages())) {
            return redirect()->to('/data-latih/create')->withInput();
        }

        $this->dataLatihModel->insert($this->getPayload());

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
            'jenisTanahOptions' => self::JENIS_TANAH_OPTIONS,
            'kelasOptions'      => self::KELAS_OPTIONS,
            'validation'        => service('validation'),
        ]);
    }

    public function update(int $id)
    {
        if ($this->dataLatihModel->find($id) === null) {
            throw PageNotFoundException::forPageNotFound('Data latih tidak ditemukan.');
        }

        if (! $this->validate($this->validationRules(), $this->validationMessages())) {
            return redirect()->to('/data-latih/' . $id . '/edit')->withInput();
        }

        $this->dataLatihModel->update($id, $this->getPayload());

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

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return redirect()->to('/data-latih')->with('error', 'File CSV kosong.');
        }

        $normalizedHeader = array_map([$this, 'normalizeHeaderValue'], $header);
        if ($normalizedHeader !== self::CSV_HEADERS) {
            fclose($handle);

            return redirect()->to('/data-latih')
                ->with('error', 'Header CSV tidak sesuai format yang dibutuhkan.')
                ->with('import_errors', [
                    'Gunakan urutan header: ' . implode(', ', self::CSV_HEADERS),
                ]);
        }

        $rows = [];
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isRowEmpty($row)) {
                continue;
            }

            if (count($row) !== count(self::CSV_HEADERS)) {
                $errors[] = 'Baris ' . $rowNumber . ': jumlah kolom tidak sesuai header.';
                continue;
            }

            $payload = array_combine(self::CSV_HEADERS, array_map('trim', $row));
            if ($payload === false) {
                $errors[] = 'Baris ' . $rowNumber . ': gagal membaca data.';
                continue;
            }

            $validationErrors = $this->validateImportRow($payload);
            if ($validationErrors !== []) {
                $errors[] = 'Baris ' . $rowNumber . ': ' . implode(' ', $validationErrors);
                continue;
            }

            $rows[] = $payload;
        }

        fclose($handle);

        if ($rows === [] && $errors === []) {
            return redirect()->to('/data-latih')->with('error', 'File CSV tidak memiliki data untuk diimpor.');
        }

        if ($errors !== []) {
            return redirect()->to('/data-latih')
                ->with('error', 'Import CSV dibatalkan karena ada data yang tidak valid.')
                ->with('import_errors', $errors);
        }

        $this->db->transStart();
        $this->dataLatihModel->insertBatch($rows);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to('/data-latih')->with('error', 'Import CSV gagal disimpan ke database.');
        }

        return redirect()->to('/data-latih')->with('success', count($rows) . ' data latih berhasil diimpor.');
    }

    private function getPayload(): array
    {
        return [
            'kemiringan_lereng' => $this->request->getPost('kemiringan_lereng'),
            'luas_lahan'        => $this->request->getPost('luas_lahan'),
            'ph_tanah'          => $this->request->getPost('ph_tanah'),
            'jenis_tanah'       => $this->request->getPost('jenis_tanah'),
            'ketinggian_tempat' => $this->request->getPost('ketinggian_tempat'),
            'kedalaman_tanah'   => $this->request->getPost('kedalaman_tanah'),
            'curah_hujan'       => $this->request->getPost('curah_hujan'),
            'kelas_tebu'        => $this->request->getPost('kelas_tebu'),
            'kelas_cabai'       => $this->request->getPost('kelas_cabai'),
            'kelas_jagung'      => $this->request->getPost('kelas_jagung'),
            'kelas_padi'        => $this->request->getPost('kelas_padi'),
        ];
    }

    private function validationRules(): array
    {
        $kelasRule = 'required|in_list[' . implode(',', self::KELAS_OPTIONS) . ']';
        $jenisTanahRule = 'required|in_list[' . implode(',', self::JENIS_TANAH_OPTIONS) . ']';

        return [
            'kemiringan_lereng' => 'required|decimal|greater_than_equal_to[0]',
            'luas_lahan'        => 'required|decimal|greater_than[0]',
            'ph_tanah'          => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[14]',
            'jenis_tanah'       => $jenisTanahRule,
            'ketinggian_tempat' => 'required|integer|greater_than_equal_to[0]',
            'kedalaman_tanah'   => 'required|integer|greater_than[0]',
            'curah_hujan'       => 'required|integer|greater_than[0]',
            'kelas_tebu'        => $kelasRule,
            'kelas_cabai'       => $kelasRule,
            'kelas_jagung'      => $kelasRule,
            'kelas_padi'        => $kelasRule,
        ];
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
            'kelas_tebu' => [
                'required' => 'Kelas tebu wajib dipilih.',
                'in_list'  => 'Kelas tebu tidak valid.',
            ],
            'kelas_cabai' => [
                'required' => 'Kelas cabai wajib dipilih.',
                'in_list'  => 'Kelas cabai tidak valid.',
            ],
            'kelas_jagung' => [
                'required' => 'Kelas jagung wajib dipilih.',
                'in_list'  => 'Kelas jagung tidak valid.',
            ],
            'kelas_padi' => [
                'required' => 'Kelas padi wajib dipilih.',
                'in_list'  => 'Kelas padi tidak valid.',
            ],
        ];
    }

    private function validateImportRow(array $payload): array
    {
        $validation = Services::validation();
        $validation->setRules($this->validationRules(), $this->validationMessages());
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
}
