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
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('id_karyawan', 20)->unique()->nullable();
            $table->string('nama', 100);
            $table->enum('status', ['staff', 'harian tetap', 'harian lepas'])->default('harian tetap');
            $table->enum('lokasi', ['workshop', 'proyek'])->default('workshop');
            $table->string('bagian', 100)->nullable();
            $table->string('jenis_proyek', 100)->nullable();
            $table->decimal('gaji_setengah_bulan', 15, 2)->nullable();
            $table->decimal('gaji_lembur', 15, 2)->nullable();
            $table->decimal('gaji_harian', 15, 2)->nullable();
            $table->decimal('uang_makan_lembur_malam', 15, 2)->nullable();
            $table->decimal('uang_makan_lembur_jalan', 15, 2)->nullable();
            $table->decimal('potongan_bpjs_kesehatan', 15, 2)->nullable();
            $table->decimal('potongan_tenaga_kerja', 15, 2)->nullable();
            $table->decimal('potongan_bpjs_kesehatan_tk', 15, 2)->nullable();
            $table->decimal('faktor_sj', 4, 2)->nullable();
            $table->decimal('faktor_sabtu', 4, 2)->nullable();
            $table->decimal('faktor_minggu', 4, 2)->nullable();
            $table->decimal('faktor_hari_besar', 4, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};
