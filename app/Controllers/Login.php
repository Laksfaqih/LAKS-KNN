<?php

namespace App\Controllers;

class Login extends BaseController
{
    public function index()
    {
        if (session('logged_in') === true) {
            return redirect()->to('/dashboard');
        }

        $data = [
            'title' => 'Login',
        ];

        return view('login', $data);
    }

    public function auth()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to('/login')->withInput()->with('error', 'Username dan password wajib diisi.');
        }

        $username = (string) $this->request->getPost('username');
        $password = (string) $this->request->getPost('password');

        $user = $this->db->table('users')
            ->where('username', $username)
            ->get()
            ->getRowArray();

        if ($user === null || ! password_verify($password, $user['password'])) {
            return redirect()->to('/login')->withInput()->with('error', 'Kredensial tidak valid.');
        }

        session()->set([
            'user_id'       => $user['id'],
            'username'      => $user['username'],
            'nama_lengkap'  => $user['nama_lengkap'],
            'logged_in'     => true,
            'last_activity' => time(),
        ]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Logout berhasil.');
    }
}
