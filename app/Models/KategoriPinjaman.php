<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriPinjaman extends Model
{
    protected $table = 'kategori_pinjaman';

    protected $fillable = [
        'nama_pinjaman', 'persentase_bunga', 'tenor_maksimal_bulan'
    ];

    public function pinjaman()
    {
        return $this->hasMany(Pinjaman::class, 'kategori_pinjaman_id');
    }
}