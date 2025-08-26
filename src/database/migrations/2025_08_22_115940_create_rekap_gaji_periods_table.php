<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rekap_gaji_periods', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->json('selected_pairs')->nullable();        // daftar lokasi–proyek dipakai
            $table->bigInteger('total_payroll')->default(0);
            $table->bigInteger('total_non_payroll')->default(0);
            $table->bigInteger('total_grand')->default(0);
            $table->unsignedInteger('count_payroll')->default(0);
            $table->unsignedInteger('count_non_payroll')->default(0);
            $table->unsignedInteger('count_grand')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // jika ingin satu rekap per periode → unikkan
            $table->unique(['start_date','end_date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('rekap_gaji_periods');
    }
};
