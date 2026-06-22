<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel pengguna (akun login)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('pengguna')
                  ->onDelete('cascade');
            
            $table->string('nomor_anggota')->unique();
            $table->string('nama');
            $table->string('nik')->unique();
            $table->string('telepon');
            $table->text('alamat');
            
            $table->string('foto_ktp')->nullable(); 
            
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};