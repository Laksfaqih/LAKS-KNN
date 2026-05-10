<?php

namespace App\Controllers;

use App\Libraries\CommodityCatalog;
use App\Libraries\SoilTypeCatalog;
use App\Models\NilaiKriteriaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;

class NilaiKriteria extends BaseController
{
    private const CLASS_OPTIONS = ['S1', 'S2', 'S3', 'N'];
    private const EXPECTED_CLASS_SET = ['S1', 'S2', 'S3', 'N'];
    private const SUPPORTED_BLOCK_HEADERS = [
        ['lereng', 'elevasi/ketinggian', 'curah hujan', 'ph tanah', 'jenis tanah', 'kedalaman tanah', 'irigasi', 'kelas'],
        ['lereng', 'elevasi ketinggian', 'curah hujan', 'ph tanah', 'jenis tanah', 'kedalaman tanah', 'irigasi', 'kelas'],
        ['lereng', 'elevasi ketinggian', 'curah hujan', 'ph tanah', 'jenis tanah', 'kedalaman', 'irigasi', 'kelas'],
    ];
    private const SOIL_CODE_MAP = [
        '1' => 'tanah lempung',
        '2' => 'tanah kubi',
        '3' => 'tanah liat',
        '4' => 'tanah berpasir',
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
            ->select('nk.*, k.nama AS nama_komoditas, k.warna_peta')
            ->join('komoditas k', 'k.id = nk.komoditas_id')
            ->orderBy('k.urutan_tampil', 'ASC')
            ->orderBy('FIELD(nk.kelas, "S1", "S2", "S3", "N")', '', false)
            ->get()
            ->getResultArray();

        return view('component/nilai_kriteria/index', [
            'title'                      => 'Nilai Kriteria',
            'nilaiKriteria'              => $rows,
            'supportedKriteriaCsvHeader' => self::SUPPORTED_BLOCK_HEADERS[0],
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
            'kelasOptions'      => self::CLASS_OPTIONS,
            'jenisTanahOptions' => SoilTypeCatalog::all(),
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
            'kelasOptions'      => self::CLASS_OPTIONS,
            'jenisTanahOptions' => SoilTypeCatalog::all(),
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

    public function import()
    {
        $file = $this->request->getFile('nilai_kriteria_csv');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return redirect()->to('/nilai-kriteria')->with('error', 'File CSV wajib dipilih.');
        }

        if (strtolower($file->getExtension()) !== 'csv') {
            return redirect()->to('/nilai-kriteria')->with('error', 'Format file harus CSV.');
        }

        $handle = fopen($file->getTempName(), 'rb');
        if ($handle === false) {
            return redirect()->to('/nilai-kriteria')->with('error', 'File CSV tidak dapat dibaca.');
        }

        $commodityMap = [];
        foreach ($this->commodityCatalog->all() as $commodity) {
            $normalizedName = mb_strtolower(trim((string) $commodity['nama']));
            $commodityMap[$normalizedName] = (int) $commodity['id'];
            if ($normalizedName === 'cabe') {
                $commodityMap['cabai'] = (int) $commodity['id'];
            }
        }

        [$rows, $errors, $commodityIds] = $this->parseImportRows($handle, $commodityMap);

        fclose($handle);

        if ($errors !== []) {
            return redirect()->to('/nilai-kriteria')->with('error', implode(' ', $errors));
        }

        if ($rows === []) {
            return redirect()->to('/nilai-kriteria')->with('error', 'Tidak ada baris valid yang bisa diimport dari CSV.');
        }

        $this->db->transStart();

        if ($commodityIds !== []) {
            $this->db->table('nilai_kriteria')->whereIn('komoditas_id', $commodityIds)->delete();
        }

        foreach ($rows as $payload) {
            $this->nilaiKriteriaModel->insert($payload);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return redirect()->to('/nilai-kriteria')->with('error', 'Import CSV gagal mengganti nilai kriteria.');
        }

        return redirect()->to('/nilai-kriteria')->with('success', count($rows) . ' baris nilai kriteria berhasil diimport dan menggantikan data lama.');
    }

    /**
     * @param array<string, int> $commodityMap
     * @return array{0: array<int, array<string, mixed>>, 1: list<string>, 2: list<int>}
     */
    private function parseImportRows($handle, array $commodityMap): array
    {
        $rows = [];
        $errors = [];
        $currentCommodityId = null;
        $classMapByCommodity = [];
        $recognizedHeader = false;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $cells = array_map(static fn(mixed $cell): string => trim((string) $cell), $row);
            $first = mb_strtolower($cells[0] ?? '');
            if ($first === '') {
                continue;
            }

            if ($this->isSupportedBlockHeader($cells)) {
                $recognizedHeader = true;
                continue;
            }

            if (($cells[1] ?? '') === '' && isset($commodityMap[$first])) {
                $currentCommodityId = $commodityMap[$first];
                continue;
            }

            if ($currentCommodityId === null) {
                continue;
            }

            $kelas = strtoupper(trim((string) ($cells[7] ?? '')));
            if (! in_array($kelas, self::CLASS_OPTIONS, true)) {
                continue;
            }

            $soil = $this->resolveSoilType($cells[4] ?? '', $cells[11] ?? '');
            if ($soil === '') {
                $errors[] = 'Kode atau label jenis tanah pada file CSV tidak dikenali.';
                continue;
            }

            [$kemMin, $kemMax] = $this->parseRangeDecimal($cells[0] ?? '');
            [$ketMin, $ketMax] = $this->parseRangeInt($cells[1] ?? '');
            [$curMin, $curMax] = $this->parseRangeInt($cells[2] ?? '');
            [$phMin, $phMax] = $this->parseRangeDecimal($cells[3] ?? '');
            [$kedMin, $kedMax] = $this->parseRangeInt($cells[5] ?? '');
            [$iriMin, $iriMax] = $this->parseRangeInt($cells[6] ?? '');

            if (isset($classMapByCommodity[$currentCommodityId][$kelas])) {
                $errors[] = 'Setiap komoditas pada CSV tidak boleh memiliki kelas duplikat.';
                continue;
            }

            $rows[] = [
                'komoditas_id'     => $currentCommodityId,
                'kelas'            => $kelas,
                'kemiringan_min'   => $kemMin,
                'kemiringan_max'   => $kemMax,
                'ph_min'           => $phMin,
                'ph_max'           => $phMax,
                'ketinggian_min'   => $ketMin,
                'ketinggian_max'   => $ketMax,
                'kedalaman_min'    => $kedMin,
                'kedalaman_max'    => $kedMax,
                'curah_hujan_min'  => $curMin,
                'curah_hujan_max'  => $curMax,
                'irigasi_min'      => $iriMin,
                'irigasi_max'      => $iriMax,
                'jenis_tanah_json' => json_encode([$soil], JSON_UNESCAPED_UNICODE),
                'catatan'          => 'import_csv_replace',
            ];

            $classMapByCommodity[$currentCommodityId][$kelas] = true;
        }

        if (! $recognizedHeader) {
            $errors[] = 'Header CSV kriteria tidak dikenali. Gunakan header blok seperti: lereng, elevasi/ketinggian, curah hujan, ph tanah, jenis tanah, kedalaman tanah, irigasi, kelas.';
        }

        foreach ($classMapByCommodity as $commodityId => $classes) {
            $present = array_keys($classes);
            sort($present);
            $expected = self::EXPECTED_CLASS_SET;
            sort($expected);

            if ($present !== $expected) {
                $errors[] = 'Setiap komoditas pada CSV harus memiliki tepat 4 kelas: S1, S2, S3, N.';
                break;
            }
        }

        if ($rows !== [] && $classMapByCommodity === []) {
            $errors[] = 'Struktur CSV tidak sesuai format kriteria yang didukung.';
        }

        return [$rows, array_values(array_unique($errors)), array_map('intval', array_keys($classMapByCommodity))];
    }

    /**
     * @param array<int, string> $cells
     */
    private function isSupportedBlockHeader(array $cells): bool
    {
        $normalized = array_map(
            static fn(string $cell): string => mb_strtolower(trim(str_replace("\xEF\xBB\xBF", '', $cell))),
            array_slice($cells, 0, 8),
        );

        foreach (self::SUPPORTED_BLOCK_HEADERS as $header) {
            if ($normalized === $header) {
                return true;
            }
        }

        return false;
    }

    private function payload(): array
    {
        return [
            'komoditas_id'     => (int) $this->request->getPost('komoditas_id'),
            'kelas'            => trim((string) $this->request->getPost('kelas')),
            'kemiringan_min'   => $this->normalizeOptionalDecimal('kemiringan_min'),
            'kemiringan_max'   => $this->normalizeOptionalDecimal('kemiringan_max'),
            'ph_min'           => $this->normalizeOptionalDecimal('ph_min'),
            'ph_max'           => $this->normalizeOptionalDecimal('ph_max'),
            'ketinggian_min'   => $this->normalizeOptionalInt('ketinggian_min'),
            'ketinggian_max'   => $this->normalizeOptionalInt('ketinggian_max'),
            'kedalaman_min'    => $this->normalizeOptionalInt('kedalaman_min'),
            'kedalaman_max'    => $this->normalizeOptionalInt('kedalaman_max'),
            'curah_hujan_min'  => $this->normalizeOptionalInt('curah_hujan_min'),
            'curah_hujan_max'  => $this->normalizeOptionalInt('curah_hujan_max'),
            'irigasi_min'      => $this->normalizeOptionalInt('irigasi_min'),
            'irigasi_max'      => $this->normalizeOptionalInt('irigasi_max'),
            'jenis_tanah_json' => json_encode($this->normalizedJenisTanahSelection(), JSON_UNESCAPED_UNICODE),
            'catatan'          => trim((string) $this->request->getPost('catatan')),
        ];
    }

    private function rules(?int $id = null): array
    {
        return [
            'komoditas_id'    => 'required|integer',
            'kelas'           => [
                'rules' => [
                    'required',
                    'in_list[' . implode(',', self::CLASS_OPTIONS) . ']',
                    fn($value, array $data, ?string &$error) => $this->isUniqueCommodityClassPair($data, $id, $error),
                ],
                'errors' => [
                    'required' => 'Kelas wajib dipilih.',
                    'in_list'  => 'Kelas tidak valid.',
                ],
            ],
            'kemiringan_min'  => 'permit_empty|decimal',
            'kemiringan_max'  => 'permit_empty|decimal',
            'ph_min'          => 'permit_empty|decimal',
            'ph_max'          => 'permit_empty|decimal',
            'ketinggian_min'  => 'permit_empty|integer',
            'ketinggian_max'  => 'permit_empty|integer',
            'kedalaman_min'   => 'permit_empty|integer',
            'kedalaman_max'   => 'permit_empty|integer',
            'curah_hujan_min' => 'permit_empty|integer',
            'curah_hujan_max' => 'permit_empty|integer',
            'irigasi_min'     => 'permit_empty|integer',
            'irigasi_max'     => 'permit_empty|integer',
            'jenis_tanah'     => [
                'rules' => [
                    static function ($value, array $data, ?string &$error, string $field): bool {
                        if ($value === null || $value === '' || $value === []) {
                            return true;
                        }

                        if (! is_array($value)) {
                            $error = 'Jenis tanah tidak valid.';

                            return false;
                        }

                        $allowed = SoilTypeCatalog::all();
                        foreach ($value as $item) {
                            if (! in_array((string) $item, $allowed, true)) {
                                $error = 'Jenis tanah tidak valid.';

                                return false;
                            }
                        }

                        return true;
                    },
                ],
                'errors' => [
                    'rules' => 'Jenis tanah tidak valid.',
                ],
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'komoditas_id' => [
                'required' => 'Komoditas wajib dipilih.',
                'integer'  => 'Komoditas tidak valid.',
            ],
        ];
    }

    private function isUniqueCommodityClassPair(array $data, ?int $ignoreId, ?string &$error): bool
    {
        $komoditasId = (int) ($data['komoditas_id'] ?? 0);
        $kelas = trim((string) ($data['kelas'] ?? ''));

        if ($komoditasId === 0 || $kelas === '') {
            return true;
        }

        $builder = $this->nilaiKriteriaModel
            ->where('komoditas_id', $komoditasId)
            ->where('kelas', $kelas);

        if ($ignoreId !== null) {
            $builder->where('id !=', $ignoreId);
        }

        if ($builder->first() !== null) {
            $error = 'Kombinasi komoditas dan kelas sudah ada.';

            return false;
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function normalizedJenisTanahSelection(): array
    {
        $jenisTanah = $this->request->getPost('jenis_tanah');
        if (! is_array($jenisTanah)) {
            return [];
        }

        $allowed = SoilTypeCatalog::all();
        $normalized = [];

        foreach ($jenisTanah as $item) {
            $item = trim((string) $item);
            if ($item === '' || ! in_array($item, $allowed, true)) {
                continue;
            }

            $normalized[$item] = $item;
        }

        return array_values($normalized);
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

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function parseRangeDecimal(string $raw): array
    {
        return $this->parseRange($raw, true, false);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function parseRangeInt(string $raw): array
    {
        [$min, $max] = $this->parseRange($raw, false, false);

        return [
            $min !== null ? (int) round($min) : null,
            $max !== null ? (int) round($max) : null,
        ];
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function parseRangeAreaHa(string $raw): array
    {
        return $this->parseRange($raw, true, true);
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    private function parseRange(string $raw, bool $decimal, bool $area): array
    {
        $text = mb_strtolower(trim($raw));
        if ($text === '' || $this->isDisjointRange($text)) {
            return [null, null];
        }

        $numberSource = $text;
        if (! str_contains($text, '<') && ! str_contains($text, '>')) {
            // Treat "-" as range separator, not negative sign.
            $numberSource = str_replace('-', ' ', $text);
        }

        preg_match_all('/\d[\d\.,]*/', $numberSource, $matches);
        $isSquareMeter = $area && str_contains($text, 'm2');
        $numbers = array_values(array_filter(array_map(
            fn(string $token): ?float => $this->toNumber($token, $decimal, $isSquareMeter),
            $matches[0] ?? [],
        ), static fn($v): bool => $v !== null));

        if ($numbers === []) {
            return [null, null];
        }

        if (str_contains($text, '<') && ! str_contains($text, '>')) {
            return [null, $numbers[0]];
        }

        if (str_contains($text, '>') && ! str_contains($text, '<')) {
            return [$numbers[0], null];
        }

        if (count($numbers) >= 2) {
            return [min($numbers[0], $numbers[1]), max($numbers[0], $numbers[1])];
        }

        return [$numbers[0], $numbers[0]];
    }

    private function isDisjointRange(string $text): bool
    {
        return str_contains($text, '&') && str_contains($text, '<') && str_contains($text, '>');
    }

    private function toNumber(string $token, bool $decimal, bool $isSquareMeter): ?float
    {
        $value = trim($token);
        if ($value === '') {
            return null;
        }

        if ($decimal) {
            if (str_contains($value, ',') && ! str_contains($value, '.')) {
                $value = str_replace(',', '.', $value);
            } elseif (! str_contains($value, ',') && str_contains($value, '.')) {
                $parts = explode('.', $value);
                if (count($parts) > 2 || (isset($parts[1]) && strlen($parts[1]) === 3)) {
                    $value = str_replace('.', '', $value);
                }
            } else {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
        } else {
            $value = str_replace(['.', ','], '', $value);
        }

        $numeric = is_numeric($value) ? (float) $value : null;
        if ($numeric === null) {
            return null;
        }

        if ($isSquareMeter) {
            return $numeric / 10000;
        }

        return $numeric;
    }

    private function normalizeSoilTypeLabel(string $value): string
    {
        $needle = mb_strtolower(trim($value));
        foreach (SoilTypeCatalog::all() as $soil) {
            if ($needle === mb_strtolower($soil)) {
                return $soil;
            }
        }

        return '';
    }

    private function resolveSoilType(string $code, string $label): string
    {
        $normalizedCode = trim($code);
        if ($normalizedCode !== '' && isset(self::SOIL_CODE_MAP[$normalizedCode])) {
            return self::SOIL_CODE_MAP[$normalizedCode];
        }

        return $this->normalizeSoilTypeLabel($label);
    }
}
