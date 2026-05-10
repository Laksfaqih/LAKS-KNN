<?php

namespace App\Models;

use CodeIgniter\Model;

class DataLatihLabelModel extends Model
{
    protected $table            = 'data_latih_label';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'data_latih_id',
        'kelas',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'            => 'integer',
        'data_latih_id' => 'integer',
    ];
}
