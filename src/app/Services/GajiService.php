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
        $karyawan = Karyawan::where('id_karyawan', $id_karyawan)->firstOrFail();

        $isHarianLepas = strtolower($karyawan->status) === 'harian lepas';

        $gaji_setengah_bulan = $isHarianLepas
            ? (float) ($karyawan->gaji_harian ?? 0)
            : (float) ($karyawan->gaji_perbulan ?? 0);

        // Ambil rekap
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

        $sj         = $rekap->sj         ?? 0;
        $sabtu      = $rekap->sabtu      ?? 0;
        $minggu     = $rekap->minggu     ?? 0;
        $hari_besar = $rekap->hari_besar ?? 0;

        $sisaSj     = (float) ($rekap->sisa_sj ?? 0);
        $sisaSabtu  = (float) ($rekap->sisa_sabtu ?? 0);
        $sisaMinggu = (float) ($rekap->sisa_minggu ?? 0);
        $sisaHB     = (float) ($rekap->sisa_hari_besar ?? 0);

        $sabtu      = (float) $sabtu      + $sisaSabtu;
        $minggu     = (float) $minggu     + $sisaMinggu;
        $hari_besar = (float) $hari_besar + $sisaHB;

        $faktorSj        = (float) ($karyawan->faktor_sj ?? 0);
        $faktorSabtu     = (float) ($karyawan->faktor_sabtu ?? 0);
        $faktorMinggu    = (float) ($karyawan->faktor_minggu ?? 0);
        $faktorHariBesar = (float) ($karyawan->faktor_hari_besar ?? 0);

        // Upah per hari juga dari DB
        $upah_per_hari = (float) ($karyawan->gaji_harian ?? 0);

        // Hitung kehadiran
        $absensi = Absensi::where('name', $karyawan->nama)
            ->whereBetween('tanggal', [$periode_awal, $periode_akhir])
            ->get();

        $total_hari_kerja  = 0;
        $total_hari_lembur = 0;

        foreach ($absensi as $absen) {
            if ($absen->masuk_pagi && $absen->pulang_kerja) $total_hari_kerja++;
            if ($absen->masuk_lembur && $absen->pulang_lembur) $total_hari_lembur++;
        }

        // Perhitungan lembur pakai faktor dari DB
        $lembur_senin_jumat = $sj        * $faktorSj;
        $lembur_sabtu       = $sabtu     * $faktorSabtu;
        $lembur_minggu      = $minggu    * $faktorMinggu;
        $lembur_hari_besar  = $hari_besar* $faktorHariBesar;

        $total_lembur = $lembur_senin_jumat + $lembur_sabtu + $lembur_minggu + $lembur_hari_besar;

        $total_upah = $total_hari_kerja * $upah_per_hari;
        // contoh: konversi lembur ke rupiah per jam dari gaji lembur atau upah harian/8
        $nominal_per_jam_lembur = (float) ($karyawan->gaji_lembur ?? ($upah_per_hari / 8));
        $total_gaji = $total_upah + ($total_lembur * $nominal_per_jam_lembur);

        return [
            'id_karyawan' => $karyawan->id_karyawan,
            'nama'        => $karyawan->nama,
            'status'      => $karyawan->status,
            'lokasi'      => $karyawan->lokasi,
            'jenis_proyek'=> $karyawan->jenis_proyek,
            'periode_awal'=> $periode_awal,
            'periode_akhir'=>$periode_akhir,

            'upah_per_hari' => $upah_per_hari,
            'gaji_setengah_bulan_nominal' => $gaji_setengah_bulan,
            'gaji_harian_nominal' => (float) ($karyawan->gaji_harian ?? 0),
            'gaji_harian_masuk'  => $rekap->jumlah_hari ?? 0,

            // Senin–Jumat
            'lembur_senin_jumat_masuk'   => $sj,
            'lembur_senin_jumat_faktor'  => $faktorSj,
            'lembur_senin_jumat_nominal' => $nominal_per_jam_lembur,
            'lembur_senin_jumat_total'   => $sj * $faktorSj * $nominal_per_jam_lembur,

            // Sabtu
            'lembur_sabtu_masuk'   => $sabtu,
            'lembur_sabtu_faktor'  => $faktorSabtu,
            'lembur_sabtu_nominal' => $nominal_per_jam_lembur,
            'lembur_sabtu_total'   => $sabtu * $faktorSabtu * $nominal_per_jam_lembur,

            // Minggu
            'lembur_minggu_masuk'   => $minggu,
            'lembur_minggu_faktor'  => $faktorMinggu,
            'lembur_minggu_nominal' => $nominal_per_jam_lembur,
            'lembur_minggu_total'   => $minggu * $faktorMinggu * $nominal_per_jam_lembur,

            // Hari Besar
            'lembur_hari_besar_masuk'   => $hari_besar,
            'lembur_hari_besar_faktor'  => $faktorHariBesar,
            'lembur_hari_besar_nominal' => $nominal_per_jam_lembur,
            'lembur_hari_besar_total'   => $hari_besar * $faktorHariBesar * $nominal_per_jam_lembur,

            // Potongan (tetap seperti sebelumnya)
            'potongan_tidak_masuk_masuk'   => $rekap->tidak_masuk ?? 0,
            'potongan_tidak_masuk_nominal' => (float) ($karyawan->gaji_lembur ?? 0),
            'potongan_tidak_masuk_total'   => ($rekap->tidak_masuk ?? 0) * (float) ($karyawan->gaji_lembur ?? 0),

            'potongan_tidak_disiplin_masuk'   => $sisaSj,
            'potongan_tidak_disiplin_nominal' => (float) ($karyawan->gaji_lembur ?? 0),
            'potongan_tidak_disiplin_total'   => $sisaSj * (float) ($karyawan->gaji_lembur ?? 0),

            'total_lembur' => $total_lembur,
            'total_gaji'   => $total_gaji,

            'nominals' => [
                'uang_makan_lembur_malam' => (float) ($karyawan->uang_makan_lembur_malam ?? 0),
                'uang_makan_lembur_jalan' => (float) ($karyawan->uang_makan_lembur_jalan ?? 0),
                'bpjs_kesehatan'          => (float) ($karyawan->potongan_bpjs_kesehatan ?? 0),
                'bpjs_tk'                 => (float) ($karyawan->potongan_tenaga_kerja ?? 0),
                'bpjs_gabungan'           => (float) ($karyawan->potongan_bpjs_kesehatan_tk ?? 0),
            ],
        ];
    }

}