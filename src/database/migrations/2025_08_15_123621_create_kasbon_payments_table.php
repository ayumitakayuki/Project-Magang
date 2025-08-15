<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kasbon_payments', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            $table->foreignId('kasbon_loan_id')
                ->constrained('kasbon_loans')
                ->cascadeOnDelete();

            $table->date('tanggal');
            $table->decimal('nominal', 14, 2);
            $table->enum('sumber', ['slip', 'manual'])->default('slip');

            // FK ke tabel gajis
            $table->foreignId('slip_gaji_id')
                ->nullable()
                ->constrained('gaji')
                ->nullOnDelete();

            $table->string('periode_label')->nullable();
            $table->string('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasbon_payments');
    }
};
