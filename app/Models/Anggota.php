<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anggota extends Model
{
    protected $table = 'anggota';

    protected $fillable = [
        'user_id', 
        'nomor_anggota', 
        'nama', 
        'nik', 
        'telepon', 
        'alamat', 
        'foto_ktp', 
        'status'
    ];

    // Relasi ke Simpanan
    public function simpanan()
    {
        return $this->hasMany(Simpanan::class, 'anggota_id');
    }

    // Relasi ke Pinjaman
    public function pinjaman()
    {
        return $this->hasMany(Pinjaman::class, 'anggota_id');
    }

    // Relasi ke Pengguna
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'user_id', 'id');
    }
}