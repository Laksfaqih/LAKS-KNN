<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'dashboard'
        ];
        return view('component/dashboard', $data);
    }
}
