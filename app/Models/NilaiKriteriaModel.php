<?php

namespace App\Models;

use CodeIgniter\Model;

class NilaiKriteriaModel extends Model
{
    protected $table            = 'nilai_kriteria';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'komoditas_id',
        'kelas',
        'kemiringan_min',
        'kemiringan_max',
        'luas_min',
        'luas_max',
        'ph_min',
        'ph_max',
        'ketinggian_min',
        'ketinggian_max',
        'kedalaman_min',
        'kedalaman_max',
        'curah_hujan_min',
        'curah_hujan_max',
        'irigasi_min',
        'irigasi_max',
        'jenis_tanah_json',
        'catatan',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
}
