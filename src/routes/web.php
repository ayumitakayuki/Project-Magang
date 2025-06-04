<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AbsensiImportController;
use Livewire\Livewire;
use App\Exports\AbsensiExport;
use Maatwebsite\Excel\Facades\Excel;


/* NOTE: Do Not Remove
/ Livewire asset handling if using sub folder in domain
*/
Livewire::setUpdateRoute(function ($handle) {
    return Route::post(config('app.asset_prefix') . '/livewire/update', $handle);
});

Livewire::setScriptRoute(function ($handle) {
    return Route::get(config('app.asset_prefix') . '/livewire/livewire.js', $handle);
});
/*
/ END
*/
Route::get('/', function () {
    return view('welcome');
});

Route::get('/absensi/download-template', [AbsensiImportController::class, 'downloadTemplate'])->name('absensi.download-template');

Route::get('/export-absensi', function (Request $request) {
    $start_date = $request->query('start_date', now()->subMonth()->toDateString());
    $end_date = $request->query('end_date', now()->toDateString());
    $id_karyawan = $request->query('id_karyawan'); // ambil id karyawan dari query

    return Excel::download(new AbsensiExport($start_date, $end_date, $id_karyawan), 'absensi_karyawan.xlsx');
});