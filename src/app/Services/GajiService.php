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
        $karyawan = Karyawan::where('id_karyawan', $id_karyawan)->firstOrFail();

        $start = Carbon::parse($periode_awal)->startOfDay();
        $end   = Carbon::parse($periode_akhir)->endOfDay();

        $isHarianLepas = strtolower((string) $karyawan->status) === 'harian lepas';
        $gaji_setengah_bulan = $isHarianLepas
            ? (float) ($karyawan->gaji_harian ?? 0)
            : (float) ($karyawan->gaji_setengah_bulan ?? 0);

        // helper kecil: "6,5" -> 6.5
        $toF = fn($v) => (float) str_replace(',', '.', (string) ($v ?? 0));

        // kandidat identitas di tabel rekap
        $argId = $id_karyawan;
        $ids = [];
        if (ctype_digit((string) $argId)) $ids[] = (int) $argId;         // legacy
        if (isset($karyawan->id))         $ids[] = (int) $karyawan->id;  // PK baru

        $nama  = $karyawan->nama;
        $awal  = $start->toDateString();
        $akhir = $end->toDateString();

        // ---------- (4a–4d) cari rekap ----------
        $awal  = $start->toDateString();
        $akhir = $end->toDateString();

        /** 1) Cari rekap **STRICT**: karyawan PK + periode exact-match */
        $rekap = $this->findRekapStrict($karyawan, $awal, $akhir);

        /** 2) Kalau belum ada, coba buat & simpan rekap lalu ambil lagi */
        if (!$rekap) {
            try {
                app(\App\Services\AbsensiRekapService::class)
                    ->rekapUntukUser($karyawan->nama, $awal, $akhir, true); // persist
            } catch (\Throwable $e) {
                // abaikan, tetap cek lagi
            }
            $rekap = $this->findRekapStrict($karyawan, $awal, $akhir);
        }

        /** 3) Jika tetap tidak ada → hentikan */
        if (!$rekap) {
            throw new \DomainException("Rekap absensi periode $awal s/d $akhir belum tersedia. Simpan rekap dulu.");
        }


        // ---------- GUARD: kalau tidak ada rekap & tidak ada absensi, stop ----------
        if (!$rekap) {
            $exists = Absensi::query()
                ->whereDate('tanggal', '>=', $awal)
                ->whereDate('tanggal', '<=', $akhir)
                ->when(Schema::hasColumn('absensis','karyawan_id'), fn($q)=>$q->where('karyawan_id',$karyawan->id))
                ->when(!Schema::hasColumn('absensis','karyawan_id') && Schema::hasColumn('absensis','id_karyawan'),
                    fn($q)=>$q->where('id_karyawan',$karyawan->id_karyawan),
                    fn($q)=>$q->where('name',$karyawan->nama))
                ->exists();

            if (!$exists) {
                throw new \DomainException('Tidak ada rekap atau absensi pada periode ini.');
            }
        }
        // ---------- /GUARD ----------

        // 5) Ekstrak angka rekap (pakai toF agar "6,5" terbaca)
        $toF = fn($v) => (float) str_replace(',', '.', (string) ($v ?? 0));

        $sj          = $toF($rekap->sj          ?? 0);
        $sabtu       = $toF($rekap->sabtu       ?? 0);
        $minggu      = $toF($rekap->minggu      ?? 0);
        $hari_besar  = $toF($rekap->hari_besar  ?? 0);
        $jumlah_hari = $toF($rekap->jumlah_hari ?? 0);
        $tidak_masuk = $toF($rekap->tidak_masuk ?? 0);

        /** utamakan sisa_jam (fallback ke sisa_sj untuk data lama) */
        $sisaJam     = $toF($rekap->sisa_jam ?? $rekap->sisa_sj ?? 0);
        $sisaSabtu   = $toF($rekap->sisa_sabtu ?? 0);
        $sisaMinggu  = $toF($rekap->sisa_minggu ?? 0);
        $sisaHB      = $toF($rekap->sisa_hari_besar ?? 0);
        $sabtu      += $sisaSabtu;
        $minggu     += $sisaMinggu;
        $hari_besar += $sisaHB;

        // 6) Faktor & tarif
        $faktorSj        = (float) ($karyawan->faktor_sj         ?? 0);
        $faktorSabtu     = (float) ($karyawan->faktor_sabtu      ?? 0);
        $faktorMinggu    = (float) ($karyawan->faktor_minggu     ?? 0);
        $faktorHariBesar = (float) ($karyawan->faktor_hari_besar ?? 0);

        $upah_per_hari          = (float) ($karyawan->gaji_harian ?? 0);
        $nominal_per_jam_normal = $upah_per_hari / 8;
        $nominal_per_jam_lembur = (float) ($karyawan->gaji_lembur ?? $nominal_per_jam_normal);

        // 8) Hitung total
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
            'potongan_tidak_masuk_nominal' => $nominal_per_jam_lembur,
            'potongan_tidak_masuk_total'   => $tidak_masuk * $nominal_per_jam_lembur,

            'potongan_tidak_disiplin_masuk'   => $sisaJam,
            'potongan_tidak_disiplin_nominal' => $nominal_per_jam_lembur,
            'potongan_tidak_disiplin_total'   => $sisaJam * $nominal_per_jam_lembur,

            'total_lembur' => $total_lembur,
            'total_gaji'   => $total_gaji,

            'kasbon_total' => $kasbon_total,
            'kasbon_items' => $kasbon_items,
            'total_gaji_bersih'  => $total_gaji - $kasbon_total,

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
    private function findRekapStrict(\App\Models\Karyawan $karyawan, string $awal, string $akhir): ?\App\Models\AbsensiRekap
    {
        return \App\Models\AbsensiRekap::query()
            ->where('karyawan_id', $karyawan->id)   // ⚠️ PK karyawans.id
            ->whereDate('periode_awal',  $awal)
            ->whereDate('periode_akhir', $akhir)
            ->orderByDesc('updated_at')
            ->first();
    }

}
