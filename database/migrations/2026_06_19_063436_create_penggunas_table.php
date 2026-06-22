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
        Schema::create('pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');
            
            // Peran akun, default-nya langsung jadi anggota pas register mandiri
            $table->enum('peran', ['admin', 'petugas', 'anggota'])->default('anggota');
            
            // Cukup ini aja buat penanda internal koperasi (bisa diset null untuk admin/petugas)
            $table->string('nomor_anggota')->nullable()->unique();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengguna');
    }

    
};