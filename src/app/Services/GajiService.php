<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use Carbon\Carbon;

class GajiService
{
    public function hitungGaji($id_karyawan, $periode_awal, $periode_akhir)
    {
        $karyawan = Karyawan::where('id_karyawan', $id_karyawan)->first();
        
        $absensi = Absensi::where('name', $karyawan->nama)
            ->whereBetween('tanggal', [$periode_awal, $periode_akhir])
            ->get();

        $total_hari_kerja = 0;
        $total_hari_lembur = 0;

        foreach ($absensi as $absen) {
            if ($absen->masuk_pagi && $absen->pulang_kerja) {
                $total_hari_kerja++;
            }
            
            if ($absen->masuk_lembur && $absen->pulang_lembur) {
                $total_hari_lembur++;
            }
        }

        $upah_per_hari = match ($karyawan->status) {
            'harian tetap' => 150000,
            'harian lepas' => 120000,
            'staff' => 200000,
            default => 0
        };

        $upah_lembur_per_hari = $upah_per_hari * 1.5;
        
        $total_upah = $total_hari_kerja * $upah_per_hari;
        $total_lembur = $total_hari_lembur * $upah_lembur_per_hari;
        $total_gaji = $total_upah + $total_lembur;

        return [
            'id_karyawan' => $karyawan->id_karyawan,
            'nama' => $karyawan->nama,
            'status' => $karyawan->status,
            'lokasi' => $karyawan->lokasi,
            'jenis_proyek' => $karyawan->jenis_proyek,
            'periode_awal' => $periode_awal,
            'periode_akhir' => $periode_akhir,
            'total_hari_kerja' => $total_hari_kerja,
            'total_hari_lembur' => $total_hari_lembur,
            'upah_per_hari' => $upah_per_hari,
            'upah_lembur_per_hari' => $upah_lembur_per_hari,
            'total_upah' => $total_upah,
            'total_lembur' => $total_lembur,
            'total_gaji' => $total_gaji
        ];
    }
}