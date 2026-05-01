<?php

namespace App\Controllers;

class Login extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'login'
        ];

        return view('login', $data);
    }
}
