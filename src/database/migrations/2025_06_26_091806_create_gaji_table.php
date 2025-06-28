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
        Schema::create('gaji', function (Blueprint $table) {
            $table->id();
            $table->string('id_karyawan');
            $table->string('nama');
            $table->string('status')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('jenis_proyek')->nullable();
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->integer('total_hari_kerja')->default(0);
            $table->integer('total_hari_lembur')->default(0);
            $table->decimal('upah_per_hari', 15, 2)->default(0);
            $table->decimal('upah_lembur_per_hari', 15, 2)->default(0);
            $table->decimal('total_upah', 15, 2)->default(0);
            $table->decimal('total_lembur', 15, 2)->default(0);
            $table->decimal('total_gaji', 15, 2)->default(0);
            $table->timestamps();

            $table->index('id_karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaji');
    }
};
