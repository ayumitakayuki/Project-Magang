<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\KasbonLoan;
use App\Models\KasbonPayment;
use Carbon\Carbon;
use App\Exports\LaporanKasbonExport;

class LaporanKasbon extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Laporan Kasbon';
    protected static ?string $title           = 'Laporan Kasbon';
    protected static ?string $navigationGroup = 'Penggajian';
    protected static string $view             = 'filament.pages.laporan-kasbon';

    public string $bulan; 
    public string $q = '';
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /** @var array<int, array> */
    public array $rows = []; 
    public array $totals = [
        'pokok' => 0, 'x' => 0,
        'sisa_prev' => 0, 'pot15' => 0,
        'pot_end' => 0, 'sisa_x' => 0, 'sisa_now' => 0,
    ];

    public function mount(): void
    {
        $this->bulan = request('bulan', now()->format('Y-m'));
        $this->q     = trim((string) request('q', ''));
        $this->loadData();
    }

    public function loadData(): void
    {
        $start   = Carbon::parse($this->bulan . '-01')->startOfMonth();
        $mid     = $start->copy()->day(15)->endOfDay();
        $end     = $start->copy()->endOfMonth()->endOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();

        $loans = KasbonLoan::with([
            'karyawan:id,nama',
            'payments' => function ($q) use ($end) {
                // muat semua pembayaran sampai akhir bulan ini (untuk sisa_prev),
                // tapi nantinya aktivitas bulan ini dihitung hanya di range [$start, $end]
                $q->whereDate('tanggal', '<=', $end);
            },
        ])
        ->when($this->q !== '', function ($q) {
            $q->whereHas('karyawan', fn ($qq) => $qq->where('nama', 'like', '%' . trim($this->q) . '%'));
        })
        ->get();

        $rows = [];

        foreach ($loans as $loan) {
            $kid  = $loan->karyawan_id;
            $nama = $loan->karyawan?->nama ?? '-';

            // sisa sampai akhir bulan lalu
            $paidPrev = (float) $loan->payments
                ->where('tanggal', '<=', $prevEnd->toDateString())
                ->sum('nominal');
            $sisaPrev = $loan->tanggal <= $prevEnd ? max(0.0, (float) $loan->pokok - $paidPrev) : 0.0;

            // kasbon baru di bulan ini (disembunyikan di tabel, tapi ikut sisa_now)
            $kasbonThis = ($loan->tanggal >= $start && $loan->tanggal <= $end) ? (float) $loan->pokok : 0.0;

            // potongan 1–15 & 16–akhir bulan ini
            $pot1 = (float) $loan->payments
                ->where('tanggal', '>=', $start->toDateString())
                ->where('tanggal', '<=', $mid->toDateString())
                ->sum('nominal');

            $pot2 = (float) $loan->payments
                ->where('tanggal', '>',  $mid->toDateString())
                ->where('tanggal', '<=', $end->toDateString())
                ->sum('nominal');

            // Sisa X per loan pada akhir bulan ini
            $paidCountToEnd = (int) $loan->payments
                ->where('tanggal', '<=', $end->toDateString())
                ->count();
            $sisaXLoan = max(0, (int) $loan->tenor - $paidCountToEnd);

            // siapkan baris karyawan
            if (!isset($rows[$kid])) {
                $rows[$kid] = [
                    'nama'      => $nama,
                    'pokok'     => 0.0,
                    'x'         => 0,
                    'sisa_prev' => 0.0,
                    'pot15'     => 0.0,
                    'pot_end'   => 0.0,
                    'sisa_x'    => 0,
                    'sisa_now'  => 0.0,
                    // field internal (tidak ditampilkan)
                    '_kasbon'   => 0.0,   // total kasbon baru bulan ini
                    '_act'      => 0.0,   // total aktivitas bulan ini (kasbon baru + pembayaran bulan ini)
                ];
            }

            // akumulasi per karyawan
            $rows[$kid]['sisa_prev'] += $sisaPrev;
            $rows[$kid]['pot15']     += $pot1;
            $rows[$kid]['pot_end']   += $pot2;
            $rows[$kid]['sisa_x']    += $sisaXLoan;
            $rows[$kid]['pokok']     += (float) $loan->pokok;
            $rows[$kid]['x']         += (int) $loan->tenor;

            // akumulasi internal untuk perhitungan & filter
            $rows[$kid]['_kasbon']   += $kasbonThis;
            $rows[$kid]['_act']      += ($kasbonThis + $pot1 + $pot2); // hanya aktivitas DI bulan ini
        }

        // hitung sisa bulan ini per baris (pakai kasbonThis juga walau tidak ditampilkan)
        foreach ($rows as $kid => $r) {
            $sisaNow = max(0.0, ($r['sisa_prev'] ?? 0) + ($r['_kasbon'] ?? 0) - ($r['pot15'] ?? 0) - ($r['pot_end'] ?? 0));
            $rows[$kid]['sisa_now'] = $sisaNow;
        }

        // TAMPILKAN HANYA baris yang punya aktivitas di bulan terpilih
        $rows = array_values(array_filter($rows, fn ($r) => (($r['_act'] ?? 0) > 0)));

        // hitung totals berdasarkan baris yang sudah difilter
        $tot = [
            'pokok'     => 0.0,
            'x'         => 0,
            'sisa_prev' => 0.0,
            'pot15'     => 0.0,
            'pot_end'   => 0.0,
            'sisa_x'    => 0,
            'sisa_now'  => 0.0,
        ];

        foreach ($rows as $r) {
            $tot['pokok']     += $r['pokok']     ?? 0;
            $tot['x']         += (int) ($r['x']  ?? 0);
            $tot['sisa_prev'] += $r['sisa_prev'] ?? 0;
            $tot['pot15']     += $r['pot15']     ?? 0;
            $tot['pot_end']   += $r['pot_end']   ?? 0;
            $tot['sisa_x']    += (int) ($r['sisa_x'] ?? 0);
            $tot['sisa_now']  += $r['sisa_now']  ?? 0;
        }

        // simpan
        $this->rows   = $rows;
        $this->totals = $tot;
    }

    public function apply(): void
    {
        $this->loadData();
    }
    public function exportPdf()
    {
        // render & stream
        return LaporanKasbonExport::fromPage($this)->streamFromPage([
            'orientation' => 'landscape',
            // 'isRemoteEnabled' => true, // aktifkan jika butuh load logo via URL
        ]);
    }

}
