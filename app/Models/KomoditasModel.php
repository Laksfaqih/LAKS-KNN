<?php

namespace App\Models;

use CodeIgniter\Model;

class KomoditasModel extends Model
{
    protected $table            = 'komoditas';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama',
        'slug',
        'warna_peta',
        'urutan_tampil',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'            => 'integer',
        'urutan_tampil' => 'integer',
        'is_active'     => 'boolean',
    ];

    public function activeOrdered(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('urutan_tampil', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
