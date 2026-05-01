<?php

namespace App\Models;

use CodeIgniter\Model;

class DataLatihKomoditasModel extends Model
{
    protected $table            = 'data_latih_komoditas';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'data_latih_id',
        'komoditas_id',
        'kelas',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'            => 'integer',
        'data_latih_id' => 'integer',
        'komoditas_id'  => 'integer',
    ];
}
