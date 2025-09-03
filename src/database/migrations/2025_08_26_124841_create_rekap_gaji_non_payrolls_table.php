<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekap_gaji_non_payrolls', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); // BIGINT UNSIGNED

            // Periode batch
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('period_label')->nullable();
            $table->enum('range_type', ['first', 'second', 'custom'])->nullable();

            // Ringkasan
            $table->unsignedInteger('rows_count')->default(0);
            $table->bigInteger('total_pembulatan')->default(0);
            $table->bigInteger('total_kasbon')->default(0);
            $table->bigInteger('total_sisa_kasbon')->default(0);
            $table->bigInteger('total_total_setelah_bon')->default(0);

            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_gaji_non_payrolls');
    }
};
