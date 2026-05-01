<?php

namespace App\Models;

use CodeIgniter\Model;

class DataLatihModel extends Model
{
    protected $table            = 'data_latih';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kemiringan_lereng',
        'luas_lahan',
        'ph_tanah',
        'jenis_tanah',
        'ketinggian_tempat',
        'kedalaman_tanah',
        'curah_hujan',
        'kelas_tebu',
        'kelas_cabai',
        'kelas_jagung',
        'kelas_padi',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'                => 'integer',
        'kemiringan_lereng' => 'float',
        'luas_lahan'        => 'float',
        'ph_tanah'          => 'float',
        'ketinggian_tempat' => 'integer',
        'kedalaman_tanah'   => 'integer',
        'curah_hujan'       => 'integer',
    ];

    public function ordered(): self
    {
        return $this->orderBy('id', 'DESC');
    }
}
