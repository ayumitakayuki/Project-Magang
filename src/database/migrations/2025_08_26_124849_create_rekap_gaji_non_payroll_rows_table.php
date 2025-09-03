<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekap_gaji_non_payroll_rows', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); // BIGINT UNSIGNED

            // FK ke header (tipe sama: unsignedBigInteger)
            $table->foreignId('rekap_gaji_non_payroll_id')
                  ->constrained('rekap_gaji_non_payrolls')
                  ->cascadeOnDelete();

            // Identitas & meta
            $table->unsignedInteger('no_urut')->nullable();
            $table->string('no_id')->nullable()->index();
            $table->string('nama')->nullable();
            $table->string('bagian')->nullable();
            $table->string('project')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('cd')->nullable();
            $table->char('plus', 1)->default('+');

            // Nilai rupiah (integer)
            $table->bigInteger('pembulatan')->default(0);        // dari GRAND
            $table->bigInteger('kasbon')->default(0);
            $table->bigInteger('sisa_kasbon')->default(0);
            $table->bigInteger('total_setelah_bon')->default(0); // biasanya = grand juga
            $table->bigInteger('total_slip')->default(0);
            $table->bigInteger('subtotal')->nullable()->default(0);

            // Periode per-row
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('period_label')->nullable();
            $table->enum('range_type', ['first', 'second', 'custom'])->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_gaji_non_payroll_rows');
    }
};
