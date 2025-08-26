<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rekap_gaji_period_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap_gaji_periods')->onDelete('cascade');
            $table->string('lokasi')->nullable();
            $table->string('proyek')->nullable();
            $table->string('keterangan'); // TRF Permata / Gaji Harian / Grand Total
            $table->string('trf')->default('-'); // payroll / non payroll / -
            $table->bigInteger('jumlah')->default(0);
            $table->unsignedInteger('jumlah_karyawan')->default(0);
            $table->timestamps();

            $table->index(['rekap_id','lokasi','proyek','keterangan']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('rekap_gaji_period_rows');
    }
};