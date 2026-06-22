<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArusKas extends Model
{
    protected $table = 'arus_kas';

    protected $fillable = [
        'jenis_kas', 'jumlah', 'keterangan', 'tanggal_transaksi'
    ];
}