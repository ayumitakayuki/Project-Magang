<?php
namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\AbsensiRekap;
use Carbon\Carbon;

class GajiService
{
    public function hitungGaji($id_karyawan, $periode_awal, $periode_akhir)
    {
        $karyawan = Karyawan::where('id_karyawan', $id_karyawan)->first();

        $isHarianLepas = strtolower($karyawan->status) === 'harian lepas';

        $gaji_setengah_bulan = $isHarianLepas
            ? ($karyawan->gaji_harian ?? 0)
            : (($karyawan->gaji_perbulan ?? 0));


        // Ambil data rekap dari absensi_rekaps
        $rekap = AbsensiRekap::where('karyawan_id', $karyawan->id_karyawan)
            ->where(function ($q) use ($periode_awal, $periode_akhir) {
                $q->whereBetween('periode_awal', [$periode_awal, $periode_akhir])
                ->orWhereBetween('periode_akhir', [$periode_awal, $periode_akhir])
                ->orWhere(function ($q2) use ($periode_awal, $periode_akhir) {
                    $q2->where('periode_awal', '<=', $periode_awal)
                        ->where('periode_akhir', '>=', $periode_akhir);
                });
            })
            ->first();


        // Ambil nilai lembur dari rekap, default 0 jika tidak ada
        $sj         = $rekap ? $rekap->sj : 0;
        $sabtu      = $rekap ? $rekap->sabtu : 0;
        $minggu     = $rekap ? $rekap->minggu : 0;
        $hari_besar = $rekap ? $rekap->hari_besar : 0;

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

        // Perhitungan lembur
        $lembur_senin_jumat = $sj * ($karyawan->faktor_lembur_sj ?? 1.5);
        $lembur_sabtu       = $sabtu * ($karyawan->faktor_lembur_sabtu ?? 1.5);
        $lembur_minggu      = $minggu * ($karyawan->faktor_lembur_minggu ?? 2.0);
        $lembur_hari_besar  = $hari_besar * ($karyawan->faktor_lembur_hari_besar ?? 2.0);

        $total_lembur = $lembur_senin_jumat + $lembur_sabtu + $lembur_minggu + $lembur_hari_besar;

        $total_upah = $total_hari_kerja * $upah_per_hari;
        $total_gaji = $total_upah + ($total_lembur * $upah_per_hari / 8); // Asumsi lembur per jam

        return [
            'id_karyawan' => $karyawan->id_karyawan,
            'nama' => $karyawan->nama,
            'status' => $karyawan->status,
            'lokasi' => $karyawan->lokasi,
            'jenis_proyek' => $karyawan->jenis_proyek,
            'periode_awal' => $periode_awal,
            'periode_akhir' => $periode_akhir,
            // 'total_hari_kerja' => $total_hari_kerja,
            'upah_per_hari' => $upah_per_hari,
            // 'total_upah' => $total_upah,
            'gaji_setengah_bulan_nominal' => $gaji_setengah_bulan,
            'gaji_harian_nominal' => $karyawan->gaji_harian ?? 0,
            'gaji_harian_masuk' => $rekap->jumlah_hari ?? 0,

            // Lembur Senin s/d Jumat
            'lembur_senin_jumat_masuk'   => $sj,
            'lembur_senin_jumat_faktor'  => $karyawan->faktor_lembur_sj ?? 1.5,
            'lembur_senin_jumat_nominal' => $karyawan->gaji_lembur ?? 0,
            'lembur_senin_jumat_total'   => $sj * ($karyawan->faktor_lembur_sj ?? 1.5) * ($karyawan->gaji_lembur ?? 0),

            // Lembur Sabtu
            'lembur_sabtu_masuk'   => $sabtu,
            'lembur_sabtu_faktor'  => $karyawan->faktor_lembur_sabtu ?? 1.5,
            'lembur_sabtu_nominal' => $karyawan->gaji_lembur ?? 0,
            'lembur_sabtu_total'   => $sabtu * ($karyawan->faktor_lembur_sabtu ?? 1.5) * ($karyawan->gaji_lembur ?? 0),

            // Lembur Minggu
            'lembur_minggu_masuk'   => $minggu,
            'lembur_minggu_faktor'  => $karyawan->faktor_lembur_minggu ?? 2.0,
            'lembur_minggu_nominal' => $karyawan->gaji_lembur ?? 0,
            'lembur_minggu_total'   => $minggu * ($karyawan->faktor_lembur_minggu ?? 2.0) * ($karyawan->gaji_lembur ?? 0),

            // Lembur Hari Besar
            'lembur_hari_besar_masuk'   => $hari_besar,
            'lembur_hari_besar_faktor'  => $karyawan->faktor_lembur_hari_besar ?? 2.0,
            'lembur_hari_besar_nominal' => $karyawan->gaji_lembur ?? 0,
            'lembur_hari_besar_total'   => $hari_besar * ($karyawan->faktor_lembur_hari_besar ?? 2.0) * ($karyawan->gaji_lembur ?? 0),

            // Potongan Gaji Tidak Masuk
            'potongan_tidak_masuk_masuk'   => $rekap ? $rekap->tidak_masuk : 0,
            'potongan_tidak_masuk_nominal' => $karyawan->gaji_lembur ?? 0,
            'potongan_tidak_masuk_total'   => ($rekap ? $rekap->tidak_masuk : 0) * ($karyawan->gaji_lembur ?? 0),

            // Potongan Gaji Tidak Disiplin
            'potongan_tidak_disiplin_masuk'   => $rekap ? $rekap->sisa_jam : 0,
            'potongan_tidak_disiplin_nominal' => $karyawan->gaji_lembur ?? 0,
            'potongan_tidak_disiplin_total'   => ($rekap ? $rekap->sisa_jam : 0) * ($karyawan->gaji_lembur ?? 0),
            
            'total_lembur' => $total_lembur,
            'total_gaji' => $total_gaji,

            'nominals' => [
                'uang_makan_lembur_malam' => $karyawan->uang_makan_lembur_malam ?? 0,
                'uang_makan_lembur_jalan' => $karyawan->uang_makan_lembur_jalan ?? 0,
                'bpjs_kesehatan' => $karyawan->potongan_bpjs_kesehatan ?? 0,
                'bpjs_tk' => $karyawan->potongan_tenaga_kerja ?? 0,
                'bpjs_gabungan' => $karyawan->potongan_bpjs_kesehatan_tk ?? 0,
            ],
        ];
    }
}