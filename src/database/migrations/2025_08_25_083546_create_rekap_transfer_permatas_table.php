<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rekap_transfer_permatas', function (Blueprint $table) {
            $table->id();

            $table->string('bank')->default('PERMATA');
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->string('range_type', 10)->nullable()->index();
            $table->string('lokasi')->nullable()->index();
            $table->string('proyek')->nullable()->index();
            $table->unsignedInteger('rows_count')->default(0);
            $table->decimal('total_pembulatan', 15, 2)->default(0);
            $table->decimal('total_kasbon', 15, 2)->default(0);
            $table->decimal('total_sisa_kasbon', 15, 2)->default(0);
            $table->decimal('total_gaji_16_31', 15, 2)->default(0);
            $table->decimal('total_gaji_15_31', 15, 2)->default(0);
            $table->decimal('total_transfer', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('rekap_transfer_permatas');
    }
};
