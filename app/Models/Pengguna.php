<?php

namespace App\Models;

// Pastikan baris "use Illuminate\Database\Eloquent\Model;" yang lama SUDAH DIHAPUS

use Illuminate\Foundation\Auth\User as Authenticatable; // Ini yang wajib ada
use Illuminate\Notifications\Notifiable;

class Pengguna extends Authenticatable 
{
    use Notifiable;

    protected $table = 'pengguna';
    
    protected $fillable = [
        'nama', 'email', 'password', 'peran'
    ];

    protected $hidden = [
        'password',
    ];

    // Tambahkan ini di dalam class Pengguna
    public function anggota()
    {
        // Pengguna memiliki SATU data profil di tabel anggota
        return $this->hasOne(Anggota::class, 'user_id', 'id');
    }

    }