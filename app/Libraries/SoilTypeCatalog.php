<?php

namespace App\Libraries;

class SoilTypeCatalog
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'tanah lempung',
            'tanah liat',
            'tanah kubi',
            'tanah berpasir',
        ];
    }
}
