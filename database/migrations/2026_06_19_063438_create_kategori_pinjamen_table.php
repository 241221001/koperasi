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
    Schema::create('kategori_pinjaman', function (Blueprint $table) {
        $table->id();
        $table->string('nama_pinjaman'); // Contoh: Pinjaman Darurat, Pinjaman Usaha
        $table->float('persentase_bunga'); // Misal: 1.5 (artinya 1.5% per bulan)
        $table->integer('tenor_maksimal_bulan');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_pinjamen');
    }
};
