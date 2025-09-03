<?php

namespace App\Services;

use App\Models\Gaji;
use App\Models\KasbonLoan;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\RekapGajiPeriod;
use App\Models\RekapGajiPeriodRow;
use Illuminate\Support\Facades\DB;

class HoRekapService
{
    public function roundTo(float $n, int $unit = 1000): array
    {
        $unit  = (int) (env('GAJI_ROUND_TO', $unit));
        $terdekat = round($n / $unit) * $unit;
        return ['rounded' => $terdekat, 'pembulatan' => $terdekat - $n];
    }
    private function normalizeTipe(?string $v): string
    {
        $t = strtolower(trim((string) $v));
        $t = str_replace(['_', ' '], '-', $t);        // samakan delimiter
        if (in_array($t, ['non-payroll', 'nonpayroll'], true)) return 'non-payroll';
        if ($t === 'payroll') return 'payroll';
        return 'payroll';
    }
    public function slipQuery(?string $start = null, ?string $end = null, ?string $lokasi = null, ?string $proyek = null, ?string $tipe = null): Builder
    {
        $q = Gaji::query()->with('details');

        if ($start && $end) {
            $s = Carbon::parse($start)->startOfDay();
            $e = Carbon::parse($end)->endOfDay();

            $q->where(function ($q) use ($s, $e) {
                $q->whereBetween('periode_awal', [$s, $e])
                  ->orWhereBetween('periode_akhir', [$s, $e])
                  ->orWhere(fn ($qq) => $qq->where('periode_awal', '<=', $s)->where('periode_akhir', '>=', $e));
            });
        }

        $q->when($lokasi, fn ($q) => $q->where('lokasi', $lokasi))
          ->when($proyek, fn ($q) => $q->where('jenis_proyek', $proyek))
          ->when($tipe, function ($q) use ($tipe) {
              $norm = strtolower(trim($tipe));
              if ($norm === 'non-payroll') {
                  $q->whereIn('tipe_pembayaran', ['non_payroll','non-payroll','Non Payroll','Non-Payroll','NON-PAYROLL']);
              } else {
                  $q->whereIn('tipe_pembayaran', ['payroll','Payroll','PAYROLL']);
              }
          });

        return $q->orderBy('periode_akhir', 'desc');
    }
    public function rekapTransferPermata(
        ?string $start = null,
        ?string $end   = null,
        ?string $lokasi = null,
        ?string $proyek = null,
        ?string $tipe   = 'payroll'
    ): array {
        $halfSelected = $this->detectHalf($start, $end);
        $slips = $this->slipQuery($start, $end, $lokasi, $proyek, $tipe)->get();

        $byEmployee = $slips->groupBy('id_karyawan');

        $rows = [];

        foreach ($byEmployee as $kid => $items) {
            /** @var \App\Models\Gaji $g */
            $g = $items->sortByDesc('periode_akhir')->first(); // ambil yang terbaru dalam range sebagai referensi

            $monthRef = $start && $end
                ? Carbon::parse($start)->startOfMonth()
                : Carbon::parse($g->periode_awal)->startOfMonth();

            if ($halfSelected === 'first') {
                $grandActive = $this->grandForHalf($kid, $monthRef, 'first');
            } elseif ($halfSelected === 'second') {
                $grandActive = $this->grandForHalf($kid, $monthRef, 'second');
            } else {
                $det = optional($g->details->where('kode','grand')->first())->total;
                if ($det === null) {
                    $sub = optional($g->details->where('kode','jml')->first())->total ?? 0;
                    $h   = optional($g->details->where('kode','h')->first())->total ?? 0;
                    $det = $sub - $h;
                }
                $grandActive = (float) $det;
            }

            $gaji15 = $this->grandForHalf($kid, $monthRef, 'first');
            $gaji16 = $this->grandForHalf($kid, $monthRef, 'second');

            if ($halfSelected === 'first') {
                $prevMonth = $monthRef->copy()->subMonth();
                $gaji16 = $this->grandForHalf($kid, $prevMonth, 'second');
            }

            $kasbon = $this->kasbonForMonth($kid, $monthRef);
            if ($halfSelected === 'first') {
                $kasbonNow  = $kasbon['pot15'];
                $sisaNow    = $kasbon['sisa_after_15'];
            } elseif ($halfSelected === 'second') {
                $kasbonNow  = $kasbon['pot_end'];
                $sisaNow    = $kasbon['sisa_now'];
            } else {
                $kasbonNow  = $kasbon['pot15'] + $kasbon['pot_end'];
                $sisaNow    = $kasbon['sisa_now'];
            }

            $rows[] = [
                'no_id'        => $g->id_karyawan,
                'bagian'       => $g->status,
                'lokasi'       => $g->lokasi,
                'project'      => $g->jenis_proyek,
                'nama'         => $g->nama,

                'pembulatan'   => $grandActive,
                'kasbon'       => $kasbonNow,
                'sisa_kasbon'  => $sisaNow,

                'gaji_16_31'   => $gaji16,
                'gaji_15_31'   => $gaji15,
            ];
        }
        return array_values($rows);
    }
    public function distinctPairs(?string $start = null, ?string $end = null): array
    {
        return $this->slipQuery($start, $end)
            ->get(['lokasi', 'jenis_proyek'])
            ->map(fn ($g) => [
                'lokasi' => $this->normLok($g->lokasi),
                'proyek' => $this->normPrj($g->jenis_proyek),
            ])
            ->unique(fn ($p) => $p['lokasi'] . '|' . $p['proyek'])
            ->sortBy(fn ($p) => sprintf('%s|%s', strtolower($p['lokasi']), strtolower($p['proyek'])))
            ->values()
            ->all();
    }
    /**
     * Rekap gaji per periode: fokus lokasi–proyek
     * @param array<int,array{lokasi:string,proyek:string}>|null $pairs  daftar filter pasangan (opsional)
     */
     public function rekapGajiPeriode(string $start, string $end, ?array $pairs = null): array
    {
        $slips = $this->slipQuery($start, $end)->get();

        $grouped = $slips->groupBy(fn ($g) =>
            ($g->lokasi ?: 'Tanpa Lokasi') . '|' . ($g->jenis_proyek ?: 'Tanpa Proyek')
        );

        $targets = $pairs && count($pairs)
            ? collect($pairs)->map(fn ($p) => ($p['lokasi'] ?: 'Tanpa Lokasi') . '|' . ($p['proyek'] ?: 'Tanpa Proyek'))
            : $grouped->keys();

        $rows = [];
        $no = 1;

        foreach ($targets as $key) {
            [$lokasi, $proyek] = explode('|', $key);
            $items = $grouped->get($key, collect());

            $payrollSum = 0; $payrollCount = 0;
            $nonSum     = 0; $nonCount     = 0;

            foreach ($items as $g) {
                $sub   = optional($g->details->where('kode', 'jml')->first())->total ?? 0;
                $kas   = optional($g->details->where('kode', 'h')->first())->total ?? 0;
                $grand = optional($g->details->where('kode', 'grand')->first())->total ?? ($sub - $kas);

                $tipe = $this->normalizeTipe($g->tipe_pembayaran);

                if ($tipe === 'non-payroll') {
                    $nonSum += $grand; $nonCount++;
                } else {
                    $payrollSum += $grand; $payrollCount++;
                }
            }

            $grandSum   = $payrollSum + $nonSum;
            $grandCount = $payrollCount + $nonCount;

            $rows[] = [
                'no_id' => $no++, 'keterangan' => 'TRF Permata',
                'lokasi' => $lokasi, 'proyek' => $proyek,
                'jumlah' => (float)$payrollSum, 'jumlah_karyawan' => (int)$payrollCount, 'trf' => 'payroll',
            ];
            $rows[] = [
                'no_id' => $no++, 'keterangan' => 'Gaji Harian',
                'lokasi' => $lokasi, 'proyek' => $proyek,
                'jumlah' => (float)$nonSum, 'jumlah_karyawan' => (int)$nonCount, 'trf' => 'non payroll',
            ];
            $rows[] = [
                'no_id' => $no++, 'keterangan' => 'Grand Total',
                'lokasi' => $lokasi, 'proyek' => $proyek,
                'jumlah' => (float)$grandSum, 'jumlah_karyawan' => (int)$grandCount, 'trf' => '-',
            ];
        }

        return $rows;
    }
    public function rekapNonPayroll(?string $start = null, ?string $end = null, ?string $lokasi = null, ?string $proyek = null): array
    {
        $rows = [];
        $slips = $this->slipQuery($start, $end, $lokasi, $proyek, 'non-payroll')->get();

        foreach ($slips as $g) {
            $sub    = optional($g->details->where('kode', 'jml')->first())->total ?? 0;
            $kasbon = optional($g->details->where('kode', 'h')->first())->total ?? 0;
            $grand  = optional($g->details->where('kode', 'grand')->first())->total ?? ($sub - $kasbon);

            $r = $this->roundTo((float) $grand);
            $pembulatan = $r['pembulatan'];

            $sisaKasbon = \App\Models\KasbonLoan::query()
                ->where('karyawan_id', $g->id_karyawan)
                ->sum('sisa_saldo');

            $rows[] = [
                'plus'        => $pembulatan >= 0 ? '+' : '-',
                'pembulatan'  => $pembulatan,
                'kasbon'      => $kasbon,
                'sisa_kasbon' => $sisaKasbon,
                'total_slip'  => $grand + $pembulatan,
                'no_id'       => $g->id_karyawan,
                'nama'        => $g->nama,
                'bagian'      => $g->status,
                'project'     => $g->jenis_proyek,
                'lokasi'      => $g->lokasi,
            ];
        }

        return $rows;
    }
    public function storeRekapGajiPeriode(string $start, string $end, ?array $pairs = null, ?int $userId = null): RekapGajiPeriod
    {
        return DB::transaction(function () use ($start, $end, $pairs, $userId) {
            // hitung dulu
            $rows = $this->rekapGajiPeriode($start, $end, $pairs);

            // agregasi total
            $totalPayroll = 0; $countPayroll = 0;
            $totalNon     = 0; $countNon     = 0;
            $totalGrand   = 0; $countGrand   = 0;

            foreach ($rows as $r) {
                if ($r['keterangan'] === 'TRF Permata') {
                    $totalPayroll += (int) $r['jumlah'];
                    $countPayroll += (int) $r['jumlah_karyawan'];
                } elseif ($r['keterangan'] === 'Gaji Harian') {
                    $totalNon += (int) $r['jumlah'];
                    $countNon += (int) $r['jumlah_karyawan'];
                } elseif ($r['keterangan'] === 'Grand Total') {
                    $totalGrand += (int) $r['jumlah'];
                    $countGrand += (int) $r['jumlah_karyawan'];
                }
            }

            // upsert header per periode
            $header = RekapGajiPeriod::updateOrCreate(
                ['start_date' => $start, 'end_date' => $end],
                [
                    'selected_pairs'   => $pairs ?: null,
                    'total_payroll'    => $totalPayroll,
                    'total_non_payroll'=> $totalNon,
                    'total_grand'      => $totalGrand,
                    'count_payroll'    => $countPayroll,
                    'count_non_payroll'=> $countNon,
                    'count_grand'      => $countGrand,
                    'created_by'       => $userId,
                ]
            );

            $header->rows()->delete();
            $payload = array_map(fn ($r) => [
                'lokasi'           => $r['lokasi'] ?? null,
                'proyek'           => $r['proyek'] ?? null,
                'keterangan'       => $r['keterangan'],
                'trf'              => $r['trf'],
                'jumlah'           => (int) $r['jumlah'],
                'jumlah_karyawan'  => (int) $r['jumlah_karyawan'],
            ], $rows);

            $header->rows()->createMany($payload);

            return $header;
        });
    }
    public function rekapPeriodeLaporan(?string $start = null, ?string $end = null, ?array $pairs = null): array
    {
        // 1) Seed pasangan dari pilihan / periode
        if (empty($pairs)) $pairs = $this->distinctPairs($start, $end);

        $agg = [];
        foreach ($pairs as $p) {
            $lok = $this->normLok($p['lokasi'] ?? null);
            $prj = $this->normPrj($p['proyek'] ?? null);
            $agg["{$lok}|{$prj}"] = [
                'lokasi'  => $lok,
                'proyek'  => $prj,
                'payroll' => ['jumlah' => 0.0, 'orang' => 0],
                'non'     => ['jumlah' => 0.0, 'orang' => 0],
            ];
        }

        // 2) Ambil slip periode + batasi pada pasangan di atas (dengan filter proyek toleran)
        $q = $this->slipQuery($start, $end);
        if (!empty($pairs)) {
            $keys = array_keys($agg);
            $q->where(function ($qq) use ($keys) {
                foreach ($keys as $k) {
                    [$lok, $prj] = explode('|', $k, 2);
                    $qq->orWhere(function ($w) use ($lok, $prj) {
                        $w->whereRaw('LOWER(lokasi) = ?', [strtolower($lok)]);
                        if ($prj === 'Tanpa Proyek') {
                            $w->where(function ($x) {
                                $x->whereNull('jenis_proyek')
                                ->orWhere('jenis_proyek', '')
                                ->orWhere('jenis_proyek', '-');
                            });
                        } else {
                            $w->where('jenis_proyek', $prj);
                        }
                    });
                }
            });
        }

        // 3) Akumulasi -> kunci pakai nilai yang sudah ternormalisasi
        foreach ($q->get() as $g) {
            $lok = $this->normLok($g->lokasi);
            $prj = $this->normPrj($g->jenis_proyek);
            $key = "{$lok}|{$prj}";

            $sub   = optional($g->details->where('kode','jml')->first())->total ?? 0;
            $kas   = optional($g->details->where('kode','h')->first())->total ?? 0;
            $grand = optional($g->details->where('kode','grand')->first())->total ?? ($sub - $kas);

            $agg[$key] ??= [
                'lokasi'  => $lok,
                'proyek'  => $prj,
                'payroll' => ['jumlah' => 0.0, 'orang' => 0],
                'non'     => ['jumlah' => 0.0, 'orang' => 0],
            ];

            if ($this->normalizeTipe($g->tipe_pembayaran) === 'payroll') {
                $agg[$key]['payroll']['jumlah'] += (float) $grand;
                $agg[$key]['payroll']['orang']  += 1;
            } else {
                $agg[$key]['non']['jumlah'] += (float) $grand;
                $agg[$key]['non']['orang']  += 1;
            }
        }

        // 4) Urut: workshop dulu, lalu proyek A–Z
        uasort($agg, function ($a, $b) {
            $aw = strcasecmp($a['lokasi'], 'workshop') === 0;
            $bw = strcasecmp($b['lokasi'], 'workshop') === 0;
            if ($aw && !$bw) return -1;
            if (!$aw && $bw) return 1;
            return strcmp($a['proyek'], $b['proyek']);
        });

        // 5) Bentuk rows (hide baris 0 untuk payroll & non-payroll)
        $rows = [];

        // Payroll block
        $no = 1; $printed = false; $sumPay = 0.0; $cntPay = 0;
        foreach ($agg as $pair) {
            if (($pair['payroll']['jumlah'] ?? 0) <= 0 && ($pair['payroll']['orang'] ?? 0) <= 0) continue;
            $rows[] = [
                'no_id'           => $printed ? '' : $no,
                'keterangan'      => 'TRF Permata',
                'lokasi'          => $pair['lokasi'],
                'proyek'          => $pair['proyek'],
                'jumlah'          => $pair['payroll']['jumlah'],
                'jumlah_karyawan' => $pair['payroll']['orang'],
                'trf'             => 'payroll',
            ];
            $printed = true;
            $sumPay += $pair['payroll']['jumlah']; $cntPay += $pair['payroll']['orang'];
        }
        if ($sumPay > 0) {
            $rows[] = [
                'no_id' => $no + 1, 'keterangan' => 'TOTAL PAYROLL',
                'lokasi' => '', 'proyek' => '',
                'jumlah' => $sumPay, 'jumlah_karyawan' => $cntPay, 'trf' => 'payroll',
            ];
        }

        // Non-payroll block
        $no = ($sumPay > 0) ? 2 : 1; // nomor seksi berikutnya
        $printed = false; $sumNon = 0.0; $cntNon = 0;
        foreach ($agg as $pair) {
            if (($pair['non']['jumlah'] ?? 0) <= 0 && ($pair['non']['orang'] ?? 0) <= 0) continue;
            $rows[] = [
                'no_id'           => $printed ? '' : $no,
                'keterangan'      => 'Gaji Harian',
                'lokasi'          => $pair['lokasi'],
                'proyek'          => $pair['proyek'],
                'jumlah'          => $pair['non']['jumlah'],
                'jumlah_karyawan' => $pair['non']['orang'],
                'trf'             => 'non payroll',
            ];
            $printed = true;
            $sumNon += $pair['non']['jumlah']; $cntNon += $pair['non']['orang'];
        }
        if ($sumNon > 0) {
            $rows[] = [
                'no_id' => $no + 1, 'keterangan' => 'TOTAL CASH',
                'lokasi' => '', 'proyek' => '',
                'jumlah' => $sumNon, 'jumlah_karyawan' => $cntNon, 'trf' => '',
            ];
        }

        // Grand total
        $lastNo = $no + 1 + ($sumNon > 0 ? 1 : 0);
        $rows[] = [
            'no_id' => $lastNo,
            'keterangan' => 'Grand Total',
            'lokasi' => '', 'proyek' => '',
            'jumlah' => $sumPay + $sumNon,
            'jumlah_karyawan' => $cntPay + $cntNon,
            'trf' => '-',
        ];

        return $rows;
    }
    private function normLok(?string $v): string
    {
        $v = trim((string) $v);
        return $v === '' ? 'workshop' : $v;
    }
    private function normPrj(?string $v): string
    {
        $v = trim((string) $v);
        if ($v === '' || $v === '-' || strcasecmp($v, 'tanpa proyek') === 0) {
            return 'Tanpa Proyek';
        }
        return $v;
    }
    private function detectHalf(?string $start, ?string $end): string
    {
        if (!$start || !$end) return 'none';

        $s = Carbon::parse($start);
        $e = Carbon::parse($end);

        $isFirst  = $s->day === 1  && $e->day === 15;
        $isSecond = $s->day >= 16  && $e->isSameDay($e->copy()->endOfMonth());

        return $isFirst ? 'first' : ($isSecond ? 'second' : 'none');
    }
    private function grandForHalf(int $karyawanId, Carbon $month, string $half): float
    {
        $from = $half === 'first'
            ? $month->copy()->startOfMonth()->startOfDay()
            : $month->copy()->day(16)->startOfDay();

        $to   = $half === 'first'
            ? $month->copy()->day(15)->endOfDay()
            : $month->copy()->endOfMonth()->endOfDay();

        $gaji = Gaji::query()
            ->with('details')
            ->where('id_karyawan', $karyawanId)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('periode_awal', [$from, $to])
                  ->orWhereBetween('periode_akhir', [$from, $to])
                  ->orWhere(fn ($qq) => $qq->where('periode_awal', '<=', $from)->where('periode_akhir', '>=', $to));
            })
            ->orderByDesc('periode_akhir')
            ->first();

        if (!$gaji) return 0.0;

        $grand = optional($gaji->details->where('kode', 'grand')->first())->total;

        // fallback bila tidak ada 'grand'
        if ($grand === null) {
            $sub   = optional($gaji->details->where('kode', 'jml')->first())->total ?? 0;
            $h     = optional($gaji->details->where('kode', 'h')->first())->total ?? 0;
            $grand = $sub - $h;
        }

        return (float) $grand;
    }

    private function kasbonForMonth(int $karyawanId, Carbon $month): array
    {
        $start   = $month->copy()->startOfMonth()->startOfDay();
        $mid     = $month->copy()->day(15)->endOfDay();
        $end     = $month->copy()->endOfMonth()->endOfDay();
        $prevEnd = $month->copy()->startOfMonth()->subDay()->endOfDay();

        // ambil semua loan + payments sampai akhir bulan
        $loans = KasbonLoan::with([
                'payments' => function ($q) use ($end) {
                    $q->whereDate('tanggal', '<=', $end);
                },
            ])
            ->where('karyawan_id', $karyawanId)
            ->get();

        $sisaPrev = 0.0; $pot15 = 0.0; $potEnd = 0.0; $kasbonThis = 0.0;

        foreach ($loans as $loan) {
            // pembayaran sampai akhir bulan lalu
            $paidPrev = (float) $loan->payments
                ->where('tanggal', '<=', $prevEnd->toDateString())
                ->sum('nominal');

            if ($loan->tanggal <= $prevEnd) {
                $sisaPrev += max(0.0, (float) $loan->pokok - $paidPrev);
            }

            // kasbon baru bulan ini
            if ($loan->tanggal >= $start && $loan->tanggal <= $end) {
                $kasbonThis += (float) $loan->pokok;
            }

            // potongan bulan ini
            $pot15 += (float) $loan->payments
                ->where('tanggal', '>=', $start->toDateString())
                ->where('tanggal', '<=', $mid->toDateString())
                ->sum('nominal');

            $potEnd += (float) $loan->payments
                ->where('tanggal', '>',  $mid->toDateString())
                ->where('tanggal', '<=', $end->toDateString())
                ->sum('nominal');
        }

        $sisaAfter15 = max(0.0, $sisaPrev + $kasbonThis - $pot15);
        $sisaNow     = max(0.0, $sisaAfter15 - $potEnd);

        return [
            'pot15'         => $pot15,
            'pot_end'       => $potEnd,
            'sisa_prev'     => $sisaPrev,
            'sisa_after_15' => $sisaAfter15,
            'sisa_now'      => $sisaNow,
        ];
    }

}

