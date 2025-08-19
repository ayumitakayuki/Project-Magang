<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gaji', function (Blueprint $table) {
            $table->string('tipe_pembayaran', 20)
                ->default('payroll')   // payroll | non_payroll
                ->index()
                ->after('periode_akhir');
        });

        DB::table('gaji')->whereNull('tipe_pembayaran')->update(['tipe_pembayaran' => 'payroll']);
    }

    public function down(): void
    {
        Schema::table('gaji', function (Blueprint $table) {
            $table->dropColumn('tipe_pembayaran');
        });
    }
};
