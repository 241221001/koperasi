<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simpanan', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('anggota_id')->constrained('anggota')->onDelete('cascade');
            $table->foreignId('kategori_simpanan_id')->constrained('kategori_simpanan');
            
            $table->enum('jenis_transaksi', ['setor', 'tarik']);
            $table->decimal('jumlah', 12, 2);
            $table->date('tanggal_transaksi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // REVISI: Samakan nama tabelnya dengan yang di atas ('simpanan' bukan 'simpanans')
        Schema::dropIfExists('simpanan'); 
    }
};