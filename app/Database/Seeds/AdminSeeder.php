<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $existing = $this->db->table('users')->where('username', 'admin')->get()->getRow();

        if ($existing !== null) {
            return;
        }

        $this->db->table('users')->insert([
            'username'     => 'admin',
            'password'     => password_hash('admin123', PASSWORD_DEFAULT),
            'nama_lengkap' => 'Administrator',
        ]);
    }
}
