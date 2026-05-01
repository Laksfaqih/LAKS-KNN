<?php

namespace App\Libraries;

use App\Models\KomoditasModel;

class CommodityCatalog
{
    public function __construct(
        private readonly KomoditasModel $komoditasModel = new KomoditasModel(),
    ) {
    }

    public function active(): array
    {
        return $this->komoditasModel->activeOrdered();
    }

    public function all(): array
    {
        return $this->komoditasModel->orderBy('urutan_tampil', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    public function idMap(bool $onlyActive = true): array
    {
        $rows = $onlyActive ? $this->active() : $this->all();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = $row;
        }

        return $map;
    }

    public function slugMap(bool $onlyActive = true): array
    {
        $rows = $onlyActive ? $this->active() : $this->all();
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['slug']] = $row;
        }

        return $map;
    }
}
