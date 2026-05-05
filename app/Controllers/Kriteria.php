<?php

namespace App\Controllers;

use App\Models\KomoditasModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Kriteria extends BaseController
{
    private KomoditasModel $komoditasModel;

    public function __construct()
    {
        $this->komoditasModel = new KomoditasModel();
    }

    public function index()
    {
        return view('component/kriteria/index', [
            'title'      => 'Kriteria',
            'komoditas'  => $this->komoditasModel->orderBy('urutan_tampil', 'ASC')->orderBy('id', 'ASC')->findAll(),
        ]);
    }

    public function create()
    {
        return view('component/kriteria/form', [
            'title'       => 'Tambah Kriteria',
            'formTitle'   => 'Tambah Kriteria',
            'formAction'  => site_url('kriteria'),
            'submitLabel' => 'Simpan Kriteria',
            'komoditas'   => null,
            'validation'  => service('validation'),
        ]);
    }

    public function store()
    {
        if (! $this->validate($this->rules(), $this->messages())) {
            return redirect()->to('/kriteria/create')->withInput();
        }

        $this->komoditasModel->insert($this->payload());

        return redirect()->to('/kriteria')->with('success', 'Kriteria berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $komoditas = $this->komoditasModel->find($id);
        if ($komoditas === null) {
            throw PageNotFoundException::forPageNotFound('Kriteria tidak ditemukan.');
        }

        return view('component/kriteria/form', [
            'title'       => 'Edit Kriteria',
            'formTitle'   => 'Edit Kriteria',
            'formAction'  => site_url('kriteria/' . $id),
            'submitLabel' => 'Perbarui Kriteria',
            'komoditas'   => $komoditas,
            'validation'  => service('validation'),
        ]);
    }

    public function update(int $id)
    {
        if ($this->komoditasModel->find($id) === null) {
            throw PageNotFoundException::forPageNotFound('Kriteria tidak ditemukan.');
        }

        if (! $this->validate($this->rules($id), $this->messages())) {
            return redirect()->to('/kriteria/' . $id . '/edit')->withInput();
        }

        $this->komoditasModel->update($id, $this->payload());

        return redirect()->to('/kriteria')->with('success', 'Kriteria berhasil diperbarui.');
    }

    public function delete(int $id)
    {
        $komoditas = $this->komoditasModel->find($id);
        if ($komoditas === null) {
            return redirect()->to('/kriteria')->with('error', 'Kriteria tidak ditemukan.');
        }

        $this->komoditasModel->delete($id);

        return redirect()->to('/kriteria')->with('success', 'Kriteria berhasil dihapus.');
    }

    private function payload(): array
    {
        return [
            'nama'          => trim((string) $this->request->getPost('nama')),
            'warna_peta'    => trim((string) $this->request->getPost('warna_peta')),
            'urutan_tampil' => (int) $this->request->getPost('urutan_tampil'),
            'is_active'     => $this->request->getPost('is_active') === '1' ? 1 : 0,
        ];
    }

    private function rules(?int $id = null): array
    {
        return [
            'nama'          => 'required|max_length[100]',
            'warna_peta'    => 'required|regex_match[/^#[A-Fa-f0-9]{6}$/]',
            'urutan_tampil' => 'required|integer|greater_than_equal_to[1]',
        ];
    }

    private function messages(): array
    {
        return [
            'nama' => ['required' => 'Nama kriteria wajib diisi.'],
            'warna_peta' => [
                'required'    => 'Warna peta wajib diisi.',
                'regex_match' => 'Warna peta harus berupa hex color, contoh #4CAF50.',
            ],
            'urutan_tampil' => [
                'required' => 'Urutan tampil wajib diisi.',
                'integer'  => 'Urutan tampil harus bilangan bulat.',
            ],
        ];
    }
}
