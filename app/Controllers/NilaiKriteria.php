<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Models\NilaiKriteriaModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class NilaiKriteria extends BaseController
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

    private NilaiKriteriaModel $nilaiKriteriaModel;
    private CommodityCatalog $commodityCatalog;

    public function __construct()
    {
        $this->nilaiKriteriaModel = new NilaiKriteriaModel();
        $this->commodityCatalog = new CommodityCatalog();
    }

    public function index()
    {
        $rows = $this->db->table('nilai_kriteria nk')
            ->select('nk.*, k.nama AS nama_komoditas, k.slug, k.warna_peta')
            ->join('komoditas k', 'k.id = nk.komoditas_id')
            ->orderBy('k.urutan_tampil', 'ASC')
            ->get()
            ->getResultArray();

        return view('component/nilai_kriteria/index', [
            'title'         => 'Nilai Kriteria',
            'nilaiKriteria' => $rows,
        ]);
    }

    public function create()
    {
        return view('component/nilai_kriteria/form', [
            'title'             => 'Tambah Nilai Kriteria',
            'formTitle'         => 'Tambah Nilai Kriteria',
            'formAction'        => site_url('nilai-kriteria'),
            'submitLabel'       => 'Simpan Nilai',
            'nilaiKriteria'     => null,
            'komoditasOptions'  => $this->commodityCatalog->all(),
            'jenisTanahOptions' => self::JENIS_TANAH_OPTIONS,
            'validation'        => service('validation'),
        ]);
    }

    public function store()
    {
        if (! $this->validate($this->rules(), $this->messages())) {
            return redirect()->to('/nilai-kriteria/create')->withInput();
        }

        $this->nilaiKriteriaModel->insert($this->payload());

        return redirect()->to('/nilai-kriteria')->with('success', 'Nilai kriteria berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $nilaiKriteria = $this->nilaiKriteriaModel->find($id);
        if ($nilaiKriteria === null) {
            throw PageNotFoundException::forPageNotFound('Nilai kriteria tidak ditemukan.');
        }

        return view('component/nilai_kriteria/form', [
            'title'             => 'Edit Nilai Kriteria',
            'formTitle'         => 'Edit Nilai Kriteria',
            'formAction'        => site_url('nilai-kriteria/' . $id),
            'submitLabel'       => 'Perbarui Nilai',
            'nilaiKriteria'     => $nilaiKriteria,
            'komoditasOptions'  => $this->commodityCatalog->all(),
            'jenisTanahOptions' => self::JENIS_TANAH_OPTIONS,
            'validation'        => service('validation'),
        ]);
    }

    public function update(int $id)
    {
        if ($this->nilaiKriteriaModel->find($id) === null) {
            throw PageNotFoundException::forPageNotFound('Nilai kriteria tidak ditemukan.');
        }

        if (! $this->validate($this->rules($id), $this->messages())) {
            return redirect()->to('/nilai-kriteria/' . $id . '/edit')->withInput();
        }

        $this->nilaiKriteriaModel->update($id, $this->payload());

        return redirect()->to('/nilai-kriteria')->with('success', 'Nilai kriteria berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        if ($this->nilaiKriteriaModel->find($id) === null) {
            return redirect()->to('/nilai-kriteria')->with('error', 'Nilai kriteria tidak ditemukan.');
        }

        $this->nilaiKriteriaModel->delete($id);

        return redirect()->to('/nilai-kriteria')->with('success', 'Nilai kriteria berhasil dihapus.');
    }

    private function payload(): array
    {
        $jenisTanah = $this->request->getPost('jenis_tanah');

        return [
            'komoditas_id'     => (int) $this->request->getPost('komoditas_id'),
            'kemiringan_min'   => $this->normalizeOptionalDecimal('kemiringan_min'),
            'kemiringan_max'   => $this->normalizeOptionalDecimal('kemiringan_max'),
            'luas_min'         => $this->normalizeOptionalDecimal('luas_min'),
            'luas_max'         => $this->normalizeOptionalDecimal('luas_max'),
            'ph_min'           => $this->normalizeOptionalDecimal('ph_min'),
            'ph_max'           => $this->normalizeOptionalDecimal('ph_max'),
            'ketinggian_min'   => $this->normalizeOptionalInt('ketinggian_min'),
            'ketinggian_max'   => $this->normalizeOptionalInt('ketinggian_max'),
            'kedalaman_min'    => $this->normalizeOptionalInt('kedalaman_min'),
            'kedalaman_max'    => $this->normalizeOptionalInt('kedalaman_max'),
            'curah_hujan_min'  => $this->normalizeOptionalInt('curah_hujan_min'),
            'curah_hujan_max'  => $this->normalizeOptionalInt('curah_hujan_max'),
            'jenis_tanah_json' => json_encode(is_array($jenisTanah) ? array_values($jenisTanah) : [], JSON_UNESCAPED_UNICODE),
            'catatan'          => trim((string) $this->request->getPost('catatan')),
        ];
    }

    private function rules(?int $id = null): array
    {
        $uniqueRule = 'required|integer|is_unique[nilai_kriteria.komoditas_id]';
        if ($id !== null) {
            $uniqueRule = 'required|integer|is_unique[nilai_kriteria.komoditas_id,id,' . $id . ']';
        }

        return [
            'komoditas_id'    => $uniqueRule,
            'kemiringan_min'  => 'permit_empty|decimal',
            'kemiringan_max'  => 'permit_empty|decimal',
            'luas_min'        => 'permit_empty|decimal',
            'luas_max'        => 'permit_empty|decimal',
            'ph_min'          => 'permit_empty|decimal',
            'ph_max'          => 'permit_empty|decimal',
            'ketinggian_min'  => 'permit_empty|integer',
            'ketinggian_max'  => 'permit_empty|integer',
            'kedalaman_min'   => 'permit_empty|integer',
            'kedalaman_max'   => 'permit_empty|integer',
            'curah_hujan_min' => 'permit_empty|integer',
            'curah_hujan_max' => 'permit_empty|integer',
        ];
    }

    private function messages(): array
    {
        return [
            'komoditas_id' => [
                'required'  => 'Komoditas wajib dipilih.',
                'is_unique' => 'Komoditas ini sudah memiliki nilai kriteria.',
            ],
        ];
    }

    private function normalizeOptionalDecimal(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key));

        return $value === '' ? null : $value;
    }

    private function normalizeOptionalInt(string $key): ?int
    {
        $value = trim((string) $this->request->getPost($key));

        return $value === '' ? null : (int) $value;
    }
}
