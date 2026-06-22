<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pinjaman extends Model
{
    protected $table = 'pinjaman';

    protected $fillable = [
        'anggota_id', 'kategori_pinjaman_id', 'jumlah_pokok', 'total_pinjaman', 'tenor_bulan', 'status', 'tanggal_disetujui'
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function kategoriPinjaman()
    {
        return $this->belongsTo(KategoriPinjaman::class, 'kategori_pinjaman_id');
    }

    // Memiliki banyak Angsuran
    public function angsuran()
    {
        return $this->hasMany(Angsuran::class, 'pinjaman_id');
    }
}