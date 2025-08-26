<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rekap_transfer_permata_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rekap_transfer_permata_id')
                  ->constrained('rekap_transfer_permatas')
                  ->cascadeOnDelete();

            $table->unsignedInteger('no_urut')->default(0);

            $table->string('no_id')->nullable()->index();
            $table->string('bagian')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('proyek')->nullable();
            $table->string('nama')->nullable();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->string('period_label')->nullable();
            $table->string('range_type', 10)->nullable()->index();
            $table->decimal('pembulatan', 15, 2)->default(0);
            $table->decimal('kasbon', 15, 2)->default(0);
            $table->decimal('sisa_kasbon', 15, 2)->default(0);
            $table->decimal('gaji_16_31', 15, 2)->default(0);
            $table->decimal('gaji_15_31', 15, 2)->default(0);
            $table->decimal('transfer', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('rekap_transfer_permata_rows');
    }
};
