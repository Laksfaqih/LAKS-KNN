<?php

namespace App\Models;

use CodeIgniter\Model;

class DataUjiContohModel extends Model
{
    protected $table            = 'data_uji_contoh';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'komoditas_id',
        'kemiringan_lereng',
        'ph_tanah',
        'jenis_tanah',
        'ketinggian_tempat',
        'kedalaman_tanah',
        'curah_hujan',
        'irigasi',
        'kelas_ekspektasi',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'                => 'integer',
        'komoditas_id'      => 'integer',
        'kemiringan_lereng' => 'float',
        'ph_tanah'          => 'float',
        'ketinggian_tempat' => 'integer',
        'kedalaman_tanah'   => 'integer',
        'curah_hujan'       => 'integer',
        'irigasi'           => 'integer',
    ];
}
