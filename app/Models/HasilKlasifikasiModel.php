<?php

namespace App\Models;

use CodeIgniter\Model;

class HasilKlasifikasiModel extends Model
{
    protected $table            = 'hasil_klasifikasi';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'data_uji_id',
        'rekomendasi_komoditas',
        'komoditas_rekomendasi_id',
        'komoditas_target_id',
        'kelas_hasil',
        'nilai_k',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'          => 'integer',
        'data_uji_id' => 'integer',
        'komoditas_rekomendasi_id' => '?integer',
        'komoditas_target_id' => '?integer',
        'nilai_k'     => 'integer',
    ];
}
