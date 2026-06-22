<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriSimpanan extends Model
{
    protected $table = 'kategori_simpanan';

    protected $fillable = [
        'nama_simpanan', 'nominal_default'
    ];

    public function simpanan()
    {
        return $this->hasMany(Simpanan::class, 'kategori_simpanan_id');
    }
}