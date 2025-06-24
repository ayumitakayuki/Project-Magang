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
            $table->integer('sj')->default(0);
            $table->integer('sabtu')->default(0);
            $table->integer('minggu')->default(0);
            $table->integer('hari_besar')->default(0);
            $table->integer('tidak_masuk')->default(0);
            $table->integer('sisa_jam')->default(0);
            $table->integer('total_jam')->default(0);
            $table->integer('jumlah_hari')->default(0);
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
