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
        Schema::create('kasbon_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('pokok',14,2);
            $table->unsignedInteger('tenor');
            $table->decimal('cicilan',14,2);
            $table->unsignedInteger('sisa_kali');
            $table->decimal('sisa_saldo',14,2);
            $table->enum('status',['aktif','lunas','ditutup'])->default('aktif');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kasbon_loans');
    }
};
