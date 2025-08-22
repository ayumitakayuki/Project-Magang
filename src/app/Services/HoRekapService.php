<?php

namespace App\Services;

use App\Models\Gaji;
use App\Models\KasbonLoan;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class HoRekapService
{
    public function roundTo(float $n, int $unit = 1000): array
    {
        // pembulatan ke ribuan terdekat (atur lewat env kalau mau)
        $unit  = (int) (env('GAJI_ROUND_TO', $unit));
        $terdekat = round($n / $unit) * $unit;
        return ['rounded' => $terdekat, 'pembulatan' => $terdekat - $n];
    }

    /** Base query slip gaji dalam rentang (overlap-aware) + filter opsional */
    public function slipQuery(string $start, string $end, ?string $lokasi = null, ?string $proyek = null, ?string $tipe = null): Builder
    {
        $s = Carbon::parse($start)->startOfDay();
        $e = Carbon::parse($end)->endOfDay();

        return Gaji::query()
            ->with('details')
            ->where(function ($q) use ($s, $e) {
                // overlap periode_awal..periode_akhir dengan $s..$e
                $q->whereBetween('periode_awal', [$s, $e])
                  ->orWhereBetween('periode_akhir', [$s, $e])
                  ->orWhere(function ($qq) use ($s, $e) {
                      $qq->where('periode_awal', '<=', $s)->where('periode_akhir', '>=', $e);
                  });
            })
            ->when($lokasi, fn($q) => $q->where('lokasi', $lokasi))
            ->when($proyek, fn($q) => $q->where('jenis_proyek', $proyek))
            ->when($tipe,   fn($q) => $q->where('tipe_pembayaran', $tipe));
    }

    /** Rekap Transfer Permata (detail per karyawan untuk transfer) */
    public function rekapTransferPermata(string $start, string $end, ?string $lokasi = null, ?string $proyek = null, ?string $tipe = 'payroll'): array
    {
        $rows = [];
        $slips = $this->slipQuery($start, $end, $lokasi, $proyek, $tipe)->get();

        foreach ($slips as $g) {
            $sub   = optional($g->details->where('kode', 'jml')->first())->total ?? 0;
            $kasbon= optional($g->details->where('kode', 'h')->first())->total ?? 0;
            $grand = optional($g->details->where('kode', 'grand')->first())->total ?? ($sub - $kasbon);

            $r = $this->roundTo((float)$grand);
            $pembulatan   = $r['pembulatan'];
            $transfer     = $r['rounded'];

            // cari sisa kasbon terakhir (opsional, jika ada modul kasbon)
            $sisaKasbon = KasbonLoan::query()
                ->where('karyawan_id', $g->id_karyawan)
                ->sum('sisa_saldo');

            $rows[] = [
                'no_id'        => $g->id_karyawan,
                'bagian'       => $g->status,
                'project'      => $g->jenis_proyek,
                'nama'         => $g->nama,
                'pembulatan'   => $pembulatan,
                'kasbon'       => $kasbon,
                'sisa_kasbon'  => $sisaKasbon,
                'transfer'     => $transfer,
                'grand'        => $grand,
                'lokasi'       => $g->lokasi,
            ];
        }

        return $rows;
    }

    /** Rekap Gaji Periode (agregat seperti sheet 1 contoh) */
    public function rekapGajiPeriode(string $start, string $end): array
    {
        $slips = $this->slipQuery($start, $end)->get();

        $groupPayroll = [];
        $groupCash    = []; // non-payroll
        foreach ($slips as $g) {
            $grand = optional($g->details->where('kode', 'grand')->first())->total ?? 0;

            $key = 'TRF PERMATA ' . strtoupper($g->jenis_proyek ?? $g->lokasi ?? 'LAINNYA');
            if (($g->tipe_pembayaran ?? 'payroll') === 'non-payroll') {
                $groupCash[$key]['jumlah'] = ($groupCash[$key]['jumlah'] ?? 0) + $grand;
                $groupCash[$key]['orang']  = ($groupCash[$key]['orang']  ?? 0) + 1;
            } else {
                $groupPayroll[$key]['jumlah'] = ($groupPayroll[$key]['jumlah'] ?? 0) + $grand;
                $groupPayroll[$key]['orang']  = ($groupPayroll[$key]['orang']  ?? 0) + 1;
            }
        }

        $totalPayroll = array_sum(array_column($groupPayroll, 'jumlah'));
        $totalOrangPayroll = array_sum(array_column($groupPayroll, 'orang'));
        $totalCash = array_sum(array_column($groupCash, 'jumlah'));
        $totalOrangCash = array_sum(array_column($groupCash, 'orang'));

        return [
            'payroll' => $groupPayroll,
            'non_payroll' => $groupCash,
            'total_payroll' => $totalPayroll,
            'total_orang_payroll' => $totalOrangPayroll,
            'total_cash' => $totalCash,
            'total_orang_cash' => $totalOrangCash,
            'grand_total' => $totalPayroll + $totalCash,
            'grand_orang' => $totalOrangPayroll + $totalOrangCash,
        ];
    }

    /** Rekap Non Payroll (detail per karyawan non-payroll) */
    public function rekapNonPayroll(string $start, string $end, ?string $lokasi = null, ?string $proyek = null): array
    {
        $rows = [];
        $slips = $this->slipQuery($start, $end, $lokasi, $proyek, 'non-payroll')->get();

        foreach ($slips as $g) {
            $sub   = optional($g->details->where('kode', 'jml')->first())->total ?? 0;
            $kasbon= optional($g->details->where('kode', 'h')->first())->total ?? 0;
            $grand = optional($g->details->where('kode', 'grand')->first())->total ?? ($sub - $kasbon);

            $r = $this->roundTo((float)$grand);
            $pembulatan = $r['pembulatan'];

            $sisaKasbon = KasbonLoan::query()
                ->where('karyawan_id', $g->id_karyawan)
                ->sum('sisa_saldo');

            $rows[] = [
                'plus'        => $pembulatan >= 0 ? '+' : '-',
                'pembulatan'  => $pembulatan,
                'kasbon'      => $kasbon,
                'sisa_kasbon' => $sisaKasbon,
                'total_slip'  => $grand + $pembulatan, // mengikuti contoh sheet: total setelah pembulatan
                'no_id'       => $g->id_karyawan,
                'nama'        => $g->nama,
                'bagian'      => $g->status,
                'project'     => $g->jenis_proyek,
            ];
        }

        return $rows;
    }
}
