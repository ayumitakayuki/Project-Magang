<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\KasbonLoan;
use App\Models\KasbonPayment;
use Carbon\Carbon;

class LaporanKasbon extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Laporan Kasbon';
    protected static ?string $title           = 'Laporan Kasbon';
    protected static ?string $navigationGroup = 'Penggajian';
    protected static string $view             = 'filament.pages.laporan-kasbon';

    // kalau hanya mau diakses dari KasbonCenter, uncomment:
    // public static function shouldRegisterNavigation(): bool { return false; }

    public string $bulan;   // format: Y-m
    public string $q = '';
    public static function shouldRegisterNavigation(): bool
    {
        return false; // ⟵ disembunyikan dari menu
    }

    /** @var array<int, array> */
    public array $rows = [];   // baris laporan per karyawan
    public array $totals = [
        'kasbon' => 0, 'sisa_prev' => 0, 'pot15' => 0, 'pot_end' => 0, 'sisa_now' => 0,
    ];

    public function mount(): void
    {
        $this->bulan = request('bulan', now()->format('Y-m'));
        $this->q     = trim((string) request('q', ''));
        $this->loadData();
    }

    public function loadData(): void
    {
        // Buat boundary tanggal
        $start = Carbon::parse($this->bulan . '-01')->startOfMonth();
        $mid   = $start->copy()->day(15)->endOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();

        // Ambil semua loan yg relevan (punya sisa sebelumnya, atau dicairkan bulan ini, atau ada pembayaran bulan ini)
        $loans = KasbonLoan::with(['karyawan:id,nama', 'payments' => function ($q) use ($end) {
                $q->whereDate('tanggal', '<=', $end); // cukup sampai akhir bulan ini (untuk hitung sisa prev & potongan)
            }])
            ->when($this->q !== '', function ($q) {
                $q->whereHas('karyawan', fn ($qq) => $qq->where('nama', 'like', '%'.trim($this->q).'%'));
            })
            ->get();

        $rows = [];
        $tot  = ['kasbon'=>0, 'sisa_prev'=>0, 'pot15'=>0, 'pot_end'=>0, 'sisa_now'=>0];

        foreach ($loans as $loan) {
            $kid   = $loan->karyawan_id;
            $nama  = $loan->karyawan?->nama ?? '-';

            // Sisa sampai akhir bulan lalu
            $paidPrev = (float) $loan->payments->where('tanggal', '<=', $prevEnd->toDateString())->sum('nominal');
            $sisaPrev = $loan->tanggal <= $prevEnd ? max(0.0, (float)$loan->pokok - $paidPrev) : 0.0;

            // Kasbon baru di bulan ini
            $kasbonThis = ($loan->tanggal >= $start && $loan->tanggal <= $end) ? (float)$loan->pokok : 0.0;

            // Potongan bulan ini (1–15 dan 16–akhir)
            $pot1 = (float) $loan->payments
                ->where('tanggal', '>=', $start->toDateString())
                ->where('tanggal', '<=', $mid->toDateString())
                ->sum('nominal');

            $pot2 = (float) $loan->payments
                ->where('tanggal', '>',  $mid->toDateString())
                ->where('tanggal', '<=', $end->toDateString())
                ->sum('nominal');

            // Akumulasi per karyawan
            if (!isset($rows[$kid])) {
                $rows[$kid] = [
                    'nama'      => $nama,
                    'kasbon'    => 0.0,
                    'sisa_prev' => 0.0,
                    'pot15'     => 0.0,
                    'pot_end'   => 0.0,
                ];
            }
            $rows[$kid]['kasbon']    += $kasbonThis;
            $rows[$kid]['sisa_prev'] += $sisaPrev;
            $rows[$kid]['pot15']     += $pot1;
            $rows[$kid]['pot_end']   += $pot2;
        }

        // Hitung sisa bulan ini & total baris
        foreach ($rows as $kid => $r) {
            $sisaNow = max(0.0, $r['sisa_prev'] + $r['kasbon'] - $r['pot15'] - $r['pot_end']);
            $rows[$kid]['sisa_now'] = $sisaNow;

            $tot['kasbon']    += $r['kasbon'];
            $tot['sisa_prev'] += $r['sisa_prev'];
            $tot['pot15']     += $r['pot15'];
            $tot['pot_end']   += $r['pot_end'];
            $tot['sisa_now']  += $sisaNow;
        }

        // Filter baris kosong (semuanya nol)
        $rows = array_values(array_filter($rows, function ($r) {
            return ($r['kasbon'] + $r['sisa_prev'] + $r['pot15'] + $r['pot_end'] + $r['sisa_now']) > 0;
        }));

        // Simpan ke properti Page
        $this->rows   = $rows;
        $this->totals = $tot;
    }

    // Dipanggil saat submit form (GET button di blade)
    public function apply(): void
    {
        $this->loadData();
    }
}
