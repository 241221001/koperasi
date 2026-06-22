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
    Schema::create('arus_kas', function (Blueprint $table) {
        $table->id();
        $table->enum('jenis_kas', ['masuk', 'keluar']);
        $table->decimal('jumlah', 12, 2);
        $table->string('keterangan'); // Misal: "Setoran Pokok Sdr. Budi"
        $table->date('tanggal_transaksi');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arus_kas');
    }
};
