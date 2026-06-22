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
    Schema::create('kategori_simpanan', function (Blueprint $table) {
        $table->id();
        $table->string('nama_simpanan'); // Contoh: Pokok, Wajib, Sukarela
        $table->decimal('nominal_default', 12, 2)->default(0); // Batas 99 Miliar
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_simpanan');
    }
};
