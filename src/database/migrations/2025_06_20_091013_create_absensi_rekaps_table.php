<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('absensi_rekaps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('karyawan_id')->nullable();
            $table->string('nama');
            $table->date('periode_awal');
            $table->date('periode_akhir');

            // Semua durasi jam pakai desimal
            $table->decimal('sj', 5, 2)->default(0);
            $table->decimal('sabtu', 5, 2)->default(0);
            $table->decimal('minggu', 5, 2)->default(0);
            $table->decimal('hari_besar', 5, 2)->default(0);
            $table->decimal('tidak_masuk', 5, 2)->default(0);
            $table->decimal('sisa_jam', 5, 2)->default(0);
            $table->decimal('sisa_sj', 5, 2)->default(0);
            $table->decimal('sisa_sabtu', 5, 2)->default(0);
            $table->decimal('sisa_minggu', 5, 2)->default(0);
            $table->decimal('sisa_hari_besar', 5, 2)->default(0);
            $table->decimal('total_jam', 5, 2)->default(0);
            $table->decimal('jumlah_hari', 5, 2)->default(0);

            $table->timestamps();
            $table->unique(['karyawan_id', 'periode_awal', 'periode_akhir']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi_rekaps');
    }
};
