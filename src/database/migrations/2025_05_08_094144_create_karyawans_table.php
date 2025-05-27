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

            // ENUM untuk status
            $table->enum('status', ['staff', 'harian tetap', 'harian lepas'])->default('harian tetap');

            // ENUM untuk lokasi
            $table->enum('lokasi', ['workshop', 'proyek'])->default('workshop');
            $table->string('jenis_proyek', 100)->nullable();

            $table->decimal('gaji_perbulan', 15, 2)->nullable();
            $table->decimal('gaji_lembur_reguler', 15, 2)->nullable();
            $table->decimal('gaji_lembur_sabtu', 15, 2)->nullable();
            $table->decimal('gaji_lembur_minggu_haribesar', 15, 2)->nullable();
            $table->decimal('gaji_harian', 15, 2)->nullable();
            
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
