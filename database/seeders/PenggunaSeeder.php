<?php

namespace Database\Seeders;

use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PenggunaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pengguna::create([
            'nama' => 'Admin Koperasi',
            'email' => 'admin@koperasi.com',
            'password' => Hash::make('password123'), // Ini password untuk login nanti
            'peran' => 'admin',
        ]);
    }
}