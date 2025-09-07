<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\AbsensiRekap;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Models\KasbonPayment;

class GajiService
{
    /**
     * $id_karyawan: KODE karyawan (bukan PK), sesuai kebiasaan kamu.
     * Contoh: "112" atau "KR-XXXX".
     */
    public function hitungGaji($id_karyawan, string $periode_awal, string $periode_akhir, ?int $slipId = null): array
    {
        // 1) Tetap: ambil karyawan via KODE (sesuai permintaan)
        $karyawan = Karyawan::where('id_karyawan', $id_karyawan)->firstOrFail();

        // 2) Rentang tanggal inclusive
        $start = Carbon::parse($periode_awal)->startOfDay();
        $end   = Carbon::parse($periode_akhir)->endOfDay();

        // 3) Status & nominal dasar
        $isHarianLepas = strtolower((string) $karyawan->status) === 'harian lepas';
        $gaji_setengah_bulan = $isHarianLepas
            ? (float) ($karyawan->gaji_harian ?? 0)
            : (float) ($karyawan->gaji_setengah_bulan ?? 0);

        // 4) Ambil REKAP
        //    - prioritaskan exact match periode
        //    - kalau tidak ada, ambil rekap yang MENCakup rentang
        //    - kalau masih tidak ada, ambil yang overlap
        //    - dukung legacy (karyawan_id = id_karyawan) & skema baru (karyawan_id = id)
        $rekapIds = [$karyawan->id]; // PK numerik
        // kalau kode-nya angka (legacy), tambahkan sebagai kandidat juga
        if (ctype_digit((string) $karyawan->id_karyawan)) {
            $rekapIds[] = (int) $karyawan->id_karyawan;
        }

        $rekap = AbsensiRekap::query()
            ->whereIn('karyawan_id', $rekapIds)
            ->whereDate('periode_awal',  $start->toDateString())
            ->whereDate('periode_akhir', $end->toDateString())
            ->first();

        if (!$rekap) {
            $rekap = AbsensiRekap::query()
                ->whereIn('karyawan_id', $rekapIds)
                ->whereDate('periode_awal',  '<=', $start->toDateString())
                ->whereDate('periode_akhir', '>=', $end->toDateString())
                ->orderByDesc('periode_akhir')
                ->first();
        }

        if (!$rekap) {
            $rekap = AbsensiRekap::query()
                ->whereIn('karyawan_id', $rekapIds)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('periode_awal',  [$start->toDateString(), $end->toDateString()])
                      ->orWhereBetween('periode_akhir', [$start->toDateString(), $end->toDateString()]);
                })
                ->orderByDesc('periode_akhir')
                ->first();
        }

        // 5) Ekstrak nilai rekap (0 jika tidak ada)
        $sj         = (float) ($rekap->sj          ?? 0);
        $sabtu      = (float) ($rekap->sabtu       ?? 0);
        $minggu     = (float) ($rekap->minggu      ?? 0);
        $hari_besar = (float) ($rekap->hari_besar  ?? 0);

        $sisaSj     = (float) ($rekap->sisa_sj            ?? 0);
        $sisaSabtu  = (float) ($rekap->sisa_sabtu         ?? 0);
        $sisaMinggu = (float) ($rekap->sisa_minggu        ?? 0);
        $sisaHB     = (float) ($rekap->sisa_hari_besar    ?? 0);

        // tambah sisa sesuai kebijakan kamu
        $sabtu      += $sisaSabtu;
        $minggu     += $sisaMinggu;
        $hari_besar += $sisaHB;

        $jumlah_hari = (float) ($rekap->jumlah_hari ?? 0);
        $tidak_masuk = (float) ($rekap->tidak_masuk ?? 0);

        // 6) Faktor & tarif
        $faktorSj        = (float) ($karyawan->faktor_sj         ?? 0);
        $faktorSabtu     = (float) ($karyawan->faktor_sabtu      ?? 0);
        $faktorMinggu    = (float) ($karyawan->faktor_minggu     ?? 0);
        $faktorHariBesar = (float) ($karyawan->faktor_hari_besar ?? 0);

        $upah_per_hari          = (float) ($karyawan->gaji_harian ?? 0);
        $nominal_per_jam_normal = $upah_per_hari / 8;
        $nominal_per_jam_lembur = (float) ($karyawan->gaji_lembur ?? $nominal_per_jam_normal);

        // 7) Fallback kalau tidak ada rekap → hitung dari ABSENSI
        //    (tanpa butuh kolom karyawan_id; pakai yang ada)
        if (!$rekap) {
            $absensiQuery = Absensi::query()
                ->whereDate('tanggal', '>=', $start->toDateString())
                ->whereDate('tanggal', '<=', $end->toDateString());

            if (Schema::hasColumn('absensis', 'karyawan_id')) {
                $absensiQuery->where('karyawan_id', $karyawan->id);
            } elseif (Schema::hasColumn('absensis', 'id_karyawan')) {
                $absensiQuery->where('id_karyawan', $karyawan->id_karyawan);
            } else {
                // skema lama: by nama
                $absensiQuery->where('name', $karyawan->nama);
            }

            $absensi = $absensiQuery->get();

            $jumlah_hari = 0;
            foreach ($absensi as $a) {
                if (!empty($a->masuk_pagi) && !empty($a->pulang_kerja)) {
                    $jumlah_hari++;
                }
            }
            // NOTE: kalau kamu simpan detil jam lembur di Absensi,
            //       agregasikan di sini juga (sekarang dibiarkan 0 kalau tidak ada rekap).
        }

        // 8) Hitung total komponen
        $lembur_senin_jumat_total = $sj        * $faktorSj       * $nominal_per_jam_lembur;
        $lembur_sabtu_total       = $sabtu     * $faktorSabtu    * $nominal_per_jam_lembur;
        $lembur_minggu_total      = $minggu    * $faktorMinggu   * $nominal_per_jam_lembur;
        $lembur_hari_besar_total  = $hari_besar* $faktorHariBesar* $nominal_per_jam_lembur;

        $total_lembur = $lembur_senin_jumat_total + $lembur_sabtu_total + $lembur_minggu_total + $lembur_hari_besar_total;
        $total_upah   = $jumlah_hari * $upah_per_hari;
        $total_gaji   = $total_upah + $total_lembur;

        $kasbonQuery = KasbonPayment::query()
            ->whereHas('loan', fn ($q) => $q->where('karyawan_id', $karyawan->id))
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->where('sumber', 'slip');

        // Saat EDIT slip, ikutkan yang sudah tertaut ke slip ini.
        // Saat CREATE slip, ambil yang belum tertaut.
        $kasbonQuery = $kasbonIdAware = $slipId
            ? $kasbonQuery->where(fn($q) => $q->whereNull('slip_gaji_id')->orWhere('slip_gaji_id', $slipId))
            : $kasbonQuery->whereNull('slip_gaji_id');

        $kasbonPayments = $kasbonQuery->orderBy('tanggal')->get();

        $kasbon_total = (float) $kasbonPayments->sum('nominal');
        $kasbon_items = $kasbonPayments->map(function ($p) {
            return [
                'id'        => $p->id,
                'loan_id'   => $p->kasbon_loan_id,
                'tanggal'   => \Carbon\Carbon::parse($p->tanggal)->toDateString(),
                'nominal'   => (float) $p->nominal,
                'catatan'   => $p->catatan,
                'sumber'    => $p->sumber,
                'tertaut'   => $p->slip_gaji_id, // null kalau belum
            ];
        })->all();

        return [
            'id_karyawan'  => $karyawan->id_karyawan, // TETAP pakai kode untuk ditampilkan/disimpan
            'nama'         => $karyawan->nama,
            'status'       => $karyawan->status,
            'lokasi'       => $karyawan->lokasi,
            'jenis_proyek' => $karyawan->jenis_proyek,
            'periode_awal' => $start->toDateString(),
            'periode_akhir'=> $end->toDateString(),

            'upah_per_hari'               => $upah_per_hari,
            'gaji_setengah_bulan_nominal' => $gaji_setengah_bulan,
            'gaji_harian_nominal'         => (float) ($karyawan->gaji_harian ?? 0),
            'gaji_harian_masuk'           => $jumlah_hari,

            // Senin–Jumat
            'lembur_senin_jumat_masuk'   => $sj,
            'lembur_senin_jumat_faktor'  => $faktorSj,
            'lembur_senin_jumat_nominal' => $nominal_per_jam_lembur,
            'lembur_senin_jumat_total'   => $lembur_senin_jumat_total,

            // Sabtu
            'lembur_sabtu_masuk'   => $sabtu,
            'lembur_sabtu_faktor'  => $faktorSabtu,
            'lembur_sabtu_nominal' => $nominal_per_jam_lembur,
            'lembur_sabtu_total'   => $lembur_sabtu_total,

            // Minggu
            'lembur_minggu_masuk'   => $minggu,
            'lembur_minggu_faktor'  => $faktorMinggu,
            'lembur_minggu_nominal' => $nominal_per_jam_lembur,
            'lembur_minggu_total'   => $lembur_minggu_total,

            // Hari Besar
            'lembur_hari_besar_masuk'   => $hari_besar,
            'lembur_hari_besar_faktor'  => $faktorHariBesar,
            'lembur_hari_besar_nominal' => $nominal_per_jam_lembur,
            'lembur_hari_besar_total'   => $lembur_hari_besar_total,

            // Potongan
            'potongan_tidak_masuk_masuk'   => $tidak_masuk,
            'potongan_tidak_masuk_nominal' => $nominal_per_jam_normal,
            'potongan_tidak_masuk_total'   => $tidak_masuk * $nominal_per_jam_normal,

            'potongan_tidak_disiplin_masuk'   => $sisaSj,
            'potongan_tidak_disiplin_nominal' => (float) ($karyawan->gaji_lembur ?? 0),
            'potongan_tidak_disiplin_total'   => $sisaSj * (float) ($karyawan->gaji_lembur ?? 0),

            'total_lembur' => $total_lembur,
            'total_gaji'   => $total_gaji,

            'kasbon_total' => $kasbon_total,
            'kasbon_items' => $kasbon_items,
            $total_gaji_bersih = $total_gaji - $kasbon_total,



            // Harga2 tambahan yg dipakai autoAddDefaultDeductions()
            'nominals' => [
                'uang_makan_lembur_malam' => (float) ($karyawan->uang_makan_lembur_malam ?? 0),
                'uang_makan_lembur_jalan' => (float) ($karyawan->uang_makan_lembur_jalan ?? 0),
                'bpjs_kesehatan'          => (float) ($karyawan->potongan_bpjs_kesehatan ?? 0),
                'bpjs_tk'                 => (float) ($karyawan->potongan_tenaga_kerja ?? 0),
                'bpjs_gabungan'           => (float) ($karyawan->potongan_bpjs_kesehatan_tk ?? 0),
            ],
        ];
    }
    public static function tautkanKasbonKeSlip(int $slipId, int $karyawanPk, string $periode_awal, string $periode_akhir): void
    {
        $awal  = \Carbon\Carbon::parse($periode_awal)->toDateString();
        $akhir = \Carbon\Carbon::parse($periode_akhir)->toDateString();

        KasbonPayment::query()
            ->whereHas('loan', fn ($q) => $q->where('karyawan_id', $karyawanPk))
            ->whereBetween('tanggal', [$awal, $akhir])
            ->where('sumber', 'slip')
            ->whereNull('slip_gaji_id')
            ->update([
                'slip_gaji_id'  => $slipId,
                'periode_label' => \Carbon\Carbon::parse($awal)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($akhir)->format('d M Y'),
            ]);
    }

}
