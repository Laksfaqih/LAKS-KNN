<?php

namespace App\Models;

use CodeIgniter\Model;

class DataUjiModel extends Model
{
    protected $table            = 'data_uji';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama_lahan',
        'kemiringan_lereng',
        'luas_lahan',
        'ph_tanah',
        'jenis_tanah',
        'ketinggian_tempat',
        'kedalaman_tanah',
        'curah_hujan',
        'latitude',
        'longitude',
        'polygon_geojson',
        'geometry_source',
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
        'latitude'          => '?float',
        'longitude'         => '?float',
    ];
}
