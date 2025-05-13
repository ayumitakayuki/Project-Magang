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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('masuk_pagi')->nullable();
            $table->string('keluar_siang')->nullable();
            $table->string('masuk_siang')->nullable();
            $table->string('pulang_kerja')->nullable();
            $table->string('masuk_lembur')->nullable();
            $table->string('pulang_lembur')->nullable();
            $table->timestamps();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
