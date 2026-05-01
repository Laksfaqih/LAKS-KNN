<?php

namespace App\Models;

use CodeIgniter\Model;

class HasilKlasifikasiDetailModel extends Model
{
    protected $table            = 'hasil_klasifikasi_detail';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'hasil_klasifikasi_id',
        'komoditas_id',
        'kelas',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'                   => 'integer',
        'hasil_klasifikasi_id' => 'integer',
        'komoditas_id'         => 'integer',
    ];
}
