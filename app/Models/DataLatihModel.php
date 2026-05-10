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
        'irigasi',
        'komoditas_id',
        'latitude',
        'longitude',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'                => 'integer',
        'kemiringan_lereng' => 'float',
        'luas_lahan'        => '?float',
        'ph_tanah'          => 'float',
        'ketinggian_tempat' => 'integer',
        'kedalaman_tanah'   => 'integer',
        'curah_hujan'       => 'integer',
        'irigasi'           => 'integer',
        'komoditas_id'      => 'integer',
        'latitude'          => '?float',
        'longitude'         => '?float',
    ];

    public function ordered(): self
    {
        return $this->orderBy('id', 'DESC');
    }
}
