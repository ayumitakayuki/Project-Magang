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
            $table->string('id_karyawan', 20)->unique();
            $table->string('nama', 100);
            $table->string('posisi', 100)->nullable();
            $table->string('bagian', 100)->nullable();
            $table->decimal('gaji_perbulan', 15, 2)->nullable();
            $table->decimal('gaji_lembur', 15, 2)->nullable();
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
