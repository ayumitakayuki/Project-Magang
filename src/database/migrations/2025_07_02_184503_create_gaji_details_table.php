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
        Schema::create('gaji_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gaji_id')->constrained('gaji')->onDelete('cascade');

            $table->string('kode');       // a, b, ..., jml, h, grand
            $table->string('keterangan');
            $table->decimal('masuk', 10, 2)->nullable();
            $table->decimal('faktor', 10, 2)->nullable();
            $table->decimal('nominal', 15, 2)->nullable();
            $table->decimal('total', 15, 2)->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaji_details');
    }
};
