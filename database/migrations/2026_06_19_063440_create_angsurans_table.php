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
    Schema::create('angsuran', function (Blueprint $table) {
        $table->id();
        // Relasi ke tabel pinjaman
        $table->foreignId('pinjaman_id')->constrained('pinjaman')->onDelete('cascade');
        
        $table->integer('angsuran_ke'); // Cicilan bulan ke-1, ke-2, dst
        $table->decimal('jumlah_bayar', 12, 2);
        $table->date('tanggal_bayar');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('angsurans');
    }
};
