<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('pinjaman', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel anggota dan kategori pinjaman
        $table->foreignId('anggota_id')->constrained('anggota')->onDelete('cascade');
        $table->foreignId('kategori_pinjaman_id')->constrained('kategori_pinjaman');
        
        $table->decimal('jumlah_pokok', 12, 2); // Nominal uang yang dipinjam
        $table->decimal('total_pinjaman', 12, 2); // Pokok + total bunga sampai lunas
        $table->integer('tenor_bulan'); // Lama cicilan (bulan)
        $table->enum('status', ['diajukan', 'disetujui', 'ditolak', 'lunas'])->default('diajukan');
        $table->date('tanggal_disetujui')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pinjaman');
    }
};
