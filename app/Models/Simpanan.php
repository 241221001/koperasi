<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simpanan extends Model
{
    protected $table = 'simpanan';

    protected $fillable = [
        'anggota_id', 'kategori_simpanan_id', 'jenis_transaksi', 'jumlah', 'tanggal_transaksi'
    ];

    // Milik Anggota siapa?
    public function anggota()
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    // Menggunakan Kategori Simpanan apa?
    public function kategoriSimpanan()
    {
        return $this->belongsTo(KategoriSimpanan::class, 'kategori_simpanan_id');
    }
}