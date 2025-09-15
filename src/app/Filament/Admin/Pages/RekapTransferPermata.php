<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\HoRekapService;
use Illuminate\Support\Arr;
use Filament\Notifications\Notification;
use App\Models\RekapTransferPermata as RekapHeader;
use App\Models\RekapTransferPermataRow as RekapRow;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Exports\RekapTransferPermataExport;
use App\Models\RekapTransferPermata as RekapTransferPermataModel;
use App\Models\KasbonLoan;
use App\Models\KasbonPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RekapTransferPermata extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Rekap Transfer Permata';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.rekap-transfer-permata';
    public ?string $search = null;
    public ?string $start_date = null;
    public ?string $end_date   = null;
    public array $rows = [];
    public array $filters = [];
    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public function mount(): void
    {

        $this->start_date = request('start_date');
        $this->end_date   = request('end_date');
        $this->search = request('search');
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->loadRows();

        // Simpan hanya jika periode dipilih lengkap
        if ($this->start_date && $this->end_date) {
            $this->persistRowsToDb();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->columns(['default' => 1, 'md' => 4])
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Periode Awal')
                            ->native(false),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Periode Akhir')
                            ->native(false),
                        Forms\Components\TextInput::make('search')
                            ->label('Search')
                            ->placeholder('Cari nama / ID / bagian / lokasi / proyek')
                            ->suffixIcon('heroicon-m-magnifying-glass')
                            ->live(debounce: 600) // auto-filter saat ngetik
                            ->afterStateUpdated(function ($state) {
                                $this->search = $state;
                                $this->loadRows(); // refresh tampilan
                            })
                            ->columnSpan(['default' => 1, 'md' => 2]),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('apply')
                                ->label('Terapkan')
                                ->color('primary')
                                ->action('applyFilters'),
                            Forms\Components\Actions\Action::make('reset')
                                ->label('Reset')
                                ->color('gray')
                                ->action(function () {
                                    $this->start_date = null;
                                    $this->end_date   = null;
                                    $this->form->fill(['start_date' => null, 'end_date' => null]);
                                    $this->loadRows();

                                    \Filament\Notifications\Notification::make()
                                        ->title('Filter direset')
                                        ->body('Menampilkan semua data.')
                                        ->success()
                                        ->send();
                                }),
                        ])->columnSpan(['default' => 1, 'md' => 4])->alignEnd(),
                    ]),
            ])
            ->statePath('filters');
    }
    public function applyFilters(): void
    {
        $state = $this->form->getState();

        $start = $state['start_date'] ?? null;
        $end   = $state['end_date']   ?? null;

        if (($start && !$end) || (!$start && $end)) {
            \Filament\Notifications\Notification::make()
                ->title('Lengkapi periode')
                ->body('Isi Periode Awal dan Periode Akhir, atau kosongkan keduanya.')
                ->warning()
                ->send();
            return;
        }

        $this->start_date = $start;
        $this->end_date   = $end;
        $this->search = $state['search'] ?? null;
        $this->loadRows();

        // Simpan hanya jika periode dipilih lengkap
        if ($this->start_date && $this->end_date) {
            $this->persistRowsToDb();
        }
    }
    private function loadRows(): void
    {
        $filterByPeriod = $this->start_date && $this->end_date;

        $this->rows = app(\App\Services\HoRekapService::class)->rekapTransferPermata(
            $filterByPeriod ? $this->start_date : null,
            $filterByPeriod ? $this->end_date   : null,
            null,
            null,
            'payroll'
        );

        if (empty($this->rows)) return;

        // ✅ ambil statistik kasbon berbasis SQL (robust)
        $kasbonByEmp = $this->getKasbonStatsForRange($this->start_date, $this->end_date);
        $codeToPk    = $this->buildEmpCodeToPkMap($this->rows);
        $slipTotals = $this->getSlipTotalsForRange($this->start_date, $this->end_date);

        $this->rows = collect($this->rows)->map(function ($r) use ($kasbonByEmp, $codeToPk, $slipTotals) {

            // --- identifikasi PK & kode ---
            $empPk = $this->resolveEmpPk($r, $codeToPk, $kasbonByEmp);
            $kode  = $r['id_karyawan'] ?? $r['no_id'] ?? null;
            $nama  = isset($r['nama']) ? mb_strtolower(trim((string)$r['nama'])) : null;

            // --- cari slip: by kode -> by pk -> by nama ---
            $fromSlip = null;
            if ($kode && isset($slipTotals['kode:'.(string)$kode])) {
                $fromSlip = $slipTotals['kode:'.(string)$kode];
            } elseif ($empPk && isset($slipTotals['pk:'.$empPk])) {
                $fromSlip = $slipTotals['pk:'.$empPk];
            } elseif ($nama && isset($slipTotals['name:'.$nama])) {
                $fromSlip = $slipTotals['name:'.$nama];
            }

            // --- kasbon/sisa default dari SQL (fallback) ---
            $stats  = $empPk !== null ? ($kasbonByEmp[$empPk] ?? ['kasbon'=>0,'sisa_kasbon'=>0])
                                    : ['kasbon'=>0,'sisa_kasbon'=>0];

            // === TOTAL GAJI (gross) ===
            // 1) pakai subtotal slip jika ada
            // 2) Fallback: pakai gaji_16_31 / gaji_15_31 / total_gaji di row (di-parse)
            $gross = $fromSlip
                ? (float) $fromSlip['subtotal']
                : ($this->parseMoney($r['gaji_16_31'] ?? null)
                ?: $this->parseMoney($r['gaji_15_31'] ?? null)
                ?: $this->parseMoney($r['total_gaji'] ?? null));

            // === FIX UTAMA: pembulatan = TOTAL GAJI (gross) ===
            $r['pembulatan'] = (float) $gross;

            // Kasbon: kalau slip ada, ikut slip; kalau tidak, fallback dari agregasi SQL
            $kasbon = $fromSlip ? (float) $fromSlip['kasbon'] : (float) ($stats['kasbon'] ?? 0);

            // Transfer/net: kalau slip ada pakai grand; kalau tidak: gross - kasbon
            $transfer = $fromSlip ? (float) $fromSlip['grand'] : max(0.0, $gross - $kasbon);

            // Tulis balik
            $r['kasbon']      = $kasbon;
            $r['sisa_kasbon'] = (int) ($stats['sisa_kasbon'] ?? 0);
            $r['transfer']    = $transfer;

            // (opsional) simpan gross eksplisit untuk audit
            $r['total_gaji']  = $gross;

            return $r;
        })->values()->all();
        // ---- Filter Search (client-side) ----
        if ($this->search && is_string($this->search)) {
            $term = Str::of($this->search)->lower()->trim()->value();

            $this->rows = collect($this->rows)->filter(function ($r) use ($term) {
                $haystacks = [
                    $r['nama']        ?? '',
                    $r['id_karyawan'] ?? '',
                    $r['no_id']       ?? '',
                    $r['bagian']      ?? '',
                    $r['lokasi']      ?? '',
                    $r['project']     ?? ($r['proyek'] ?? ''),
                ];

                // gabung dan bandingkan lowercase
                $hay = Str::of(implode(' ', $haystacks))->lower()->value();
                return Str::contains($hay, $term);
            })->values()->all();
        }

    }
    private function getSlipTotalsForRange(?string $startDate, ?string $endDate): array
    {
        if (!$startDate || !$endDate) return [];

        $slips = \App\Models\Gaji::query()
            ->with('details')
            ->whereDate('periode_awal',  $startDate)
            ->whereDate('periode_akhir', $endDate)
            // ->where('tipe_pembayaran','payroll') // uncomment kalau wajib payroll
            ->get();

        $out = [];
        foreach ($slips as $g) {
            $get = fn($k) => (float) (optional($g->details->where('kode',$k)->first())->total ?? 0);

            // indeks utama: kode karyawan (id_karyawan)
            if ($g->id_karyawan) {
                $out['kode:'.(string)$g->id_karyawan] = [
                    'subtotal' => $get('jml'),
                    'kasbon'   => $get('h'),
                    'grand'    => $get('grand'),
                    'karyawan_id' => $g->karyawan_id,
                    'nama'        => mb_strtolower(trim((string)$g->nama)),
                ];
            }
            // indeks alternatif: PK
            if ($g->karyawan_id) {
                $out['pk:'.$g->karyawan_id] = [
                    'subtotal' => $get('jml'),
                    'kasbon'   => $get('h'),
                    'grand'    => $get('grand'),
                    'karyawan_id' => $g->karyawan_id,
                    'nama'        => mb_strtolower(trim((string)$g->nama)),
                ];
            }
            // indeks alternatif: nama (last resort)
            $nameKey = 'name:'.mb_strtolower(trim((string)$g->nama));
            if ($g->nama) {
                $out[$nameKey] = [
                    'subtotal' => $get('jml'),
                    'kasbon'   => $get('h'),
                    'grand'    => $get('grand'),
                    'karyawan_id' => $g->karyawan_id,
                    'nama'        => mb_strtolower(trim((string)$g->nama)),
                ];
            }
        }
        return $out;
    }

    private function parseMoney($v): float
    {
        // "Rp 3.548.438" -> 3548438
        return (float) preg_replace('/[^\d.-]/','', (string)($v ?? 0));
    }



    private function roundAdjustment(float $amount, int $base = 1000, string $mode = 'nearest'): float
    {
        if ($base <= 0) return 0.0;

        switch ($mode) {
            case 'up':
                $rounded = ceil($amount / $base) * $base;
                break;
            case 'down':
                $rounded = floor($amount / $base) * $base;
                break;
            default: // 'nearest'
                $rounded = round($amount / $base) * $base;
                break;
        }

        return (float) ($rounded - $amount);
    }


    private function persistRowsToDb(): void
    {
        if (!$this->start_date || !$this->end_date) return;

        $start = \Carbon\Carbon::parse($this->start_date);
        $end   = \Carbon\Carbon::parse($this->end_date);

        $lastDay = $start->copy()->endOfMonth()->day;
        if ($start->day === 1 && $end->day === 15) {
            $rangeType   = 'first';
            $periodLabel = '01–15 ' . $start->format('F Y');
        } elseif ($start->day >= 16 && $end->day === $lastDay) {
            $rangeType   = 'second';
            $periodLabel = '16–' . $lastDay . ' ' . $start->format('F Y');
        } else {
            $rangeType   = 'custom';
            $periodLabel = $start->format('d M Y') . ' – ' . $end->format('d M Y');
        }

        if (empty($this->rows)) $this->loadRows();
        $rowsCount = count($this->rows);
        if ($rowsCount === 0) {
            \Filament\Notifications\Notification::make()
                ->title('Tidak ada data')
                ->body("Tidak ada baris untuk periode {$periodLabel}. Rekap tidak disimpan.")
                ->warning()->send();
            return;
        }

        // gunakan koneksi yang sama dengan model header
        $connName = (new \App\Models\RekapTransferPermata())->getConnectionName() ?: config('database.default');
        $db = \Illuminate\Support\Facades\DB::connection($connName);

        try {
            $db->transaction(function () use ($db, $start, $end, $rangeType, $periodLabel, $rowsCount) {

                // HEADER (pastikan ada)
                /** @var \App\Models\RekapTransferPermata $header */
                $header = \App\Models\RekapTransferPermata::on($db->getName())->firstOrCreate(
                    [
                        'bank'         => 'PERMATA',
                        'period_start' => $start->toDateString(),
                        'period_end'   => $end->toDateString(),
                    ],
                    ['range_type'   => $rangeType]
                );

                // Hapus detail lama di periode ini
                $db->table('rekap_transfer_permata_rows')
                ->where('rekap_transfer_permata_id', $header->id)
                ->delete();

                // Siapkan payload lengkap (FK + kolom periode)
                $now = now();
                $rowsPayload = [];
                foreach (array_values($this->rows) as $i => $r) {
                    $rowsPayload[] = [
                        'rekap_transfer_permata_id' => $header->id,
                        'no_urut'     => $i + 1,
                        'no_id'       => $r['no_id']   ?? null,
                        'bagian'      => $r['bagian']  ?? null,
                        'lokasi'      => $r['lokasi']  ?? null,
                        'proyek'      => $r['project'] ?? ($r['proyek'] ?? null),
                        'nama'        => $r['nama']    ?? null,

                        'period_start' => $start->toDateString(),
                        'period_end'   => $end->toDateString(),
                        'period_label' => $periodLabel,
                        'range_type'   => $rangeType,

                        // paksa numerik, kalau sumber string "Rp 1.000.000", bersihkan dulu
                        'pembulatan'  => (float) preg_replace('/[^\d.-]/', '', (string) ($r['pembulatan']  ?? 0)),
                        'kasbon'      => (float) preg_replace('/[^\d.-]/', '', (string) ($r['kasbon']      ?? 0)),
                        'sisa_kasbon' => (float) preg_replace('/[^\d.-]/', '', (string) ($r['sisa_kasbon'] ?? 0)),
                        'gaji_16_31'  => (float) preg_replace('/[^\d.-]/', '', (string) ($r['gaji_16_31']  ?? 0)),
                        'gaji_15_31'  => (float) preg_replace('/[^\d.-]/', '', (string) ($r['gaji_15_31']  ?? 0)),
                        'transfer'    => (float) preg_replace('/[^\d.-]/', '', (string) ($r['transfer']    ?? 0)),

                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }

                // log sample untuk verifikasi
                Log::info('permata.persist payload', [
                    'connection' => $db->getName(),
                    'header_id'  => $header->id,
                    'rows'       => $rowsCount,
                    'sample'     => $rowsPayload[0] ?? null,
                ]);

                // INSERT detail
                $db->table('rekap_transfer_permata_rows')->insert($rowsPayload);

                // agregasi dari tabel detail
                $agg = $db->table('rekap_transfer_permata_rows')
                    ->where('rekap_transfer_permata_id', $header->id)
                    ->selectRaw("
                        COUNT(*)                       AS rows_count,
                        COALESCE(SUM(pembulatan),   0) AS total_pembulatan,
                        COALESCE(SUM(kasbon),       0) AS total_kasbon,
                        COALESCE(SUM(sisa_kasbon),  0) AS total_sisa_kasbon,
                        COALESCE(SUM(gaji_16_31),   0) AS total_gaji_16_31,
                        COALESCE(SUM(gaji_15_31),   0) AS total_gaji_15_31,
                        COALESCE(SUM(transfer),     0) AS total_transfer
                    ")->first();

                Log::info('permata.persist aggregate', [
                    'header_id' => $header->id,
                    'agg'       => (array) $agg,
                ]);

                // UPDATE header totals
                $db->table('rekap_transfer_permatas')
                ->where('id', $header->id)
                ->update([
                    'range_type'        => $rangeType,
                    'rows_count'        => (int) ($agg->rows_count ?? 0),
                    'total_pembulatan'  => (float) ($agg->total_pembulatan ?? 0),
                    'total_kasbon'      => (float) ($agg->total_kasbon ?? 0),
                    'total_sisa_kasbon' => (float) ($agg->total_sisa_kasbon ?? 0),
                    'total_gaji_16_31'  => (float) ($agg->total_gaji_16_31 ?? 0),
                    'total_gaji_15_31'  => (float) ($agg->total_gaji_15_31 ?? 0),
                    'total_transfer'    => (float) ($agg->total_transfer ?? 0),
                    'updated_at'        => $now,
                ]);
            });

            \Filament\Notifications\Notification::make()
                ->title('Rekap tersimpan')
                ->body("Rekap Transfer PERMATA periode {$periodLabel} berhasil disimpan ({$rowsCount} baris).")
                ->success()->send();

        } catch (\Throwable $e) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal menyimpan')
                ->body('Terjadi kesalahan saat menyimpan rekap: ' . $e->getMessage())
                ->danger()->send();
            report($e);
        }
    }

    public function exportPdf()
    {
        if (!$this->start_date || !$this->end_date) {
            Notification::make()
                ->title('Pilih periode dulu')
                ->body('Silakan isi Periode Awal & Akhir, baru unduh PDF.')
                ->warning()
                ->send();
            return;
        }

        // Pastikan data periode ini sudah tersimpan
        $this->persistRowsToDb();

        // Ambil header batch untuk periode yang dipilih
        $header = RekapTransferPermataModel::query()
            ->where('bank', 'PERMATA')
            ->whereDate('period_start', $this->start_date)
            ->whereDate('period_end', $this->end_date)
            ->first();

        if (!$header || !$header->rows()->exists()) {
            Notification::make()
                ->title('Tidak ada data')
                ->body('Tidak ada baris untuk periode ini.')
                ->warning()
                ->send();
            return;
        }

        return (new \App\Exports\RekapTransferPermataExport([$header->id]))->download();
    }
    private function getKasbonStatsForRange(?string $startDate, ?string $endDate): array
    {
        $end   = $endDate   ? \Carbon\Carbon::parse($endDate)->toDateString()   : now()->toDateString();
        $start = $startDate ? \Carbon\Carbon::parse($startDate)->toDateString() : null;

        $payDate = DB::raw("DATE(COALESCE(kasbon_payments.tanggal, kasbon_payments.created_at))");

        // 1) Kasbon (pembayaran) DI DALAM range (atau s/d end jika start null)
        $paidInRange = \App\Models\KasbonLoan::query()
            ->join('kasbon_payments', 'kasbon_payments.kasbon_loan_id', '=', 'kasbon_loans.id')
            ->whereDate('kasbon_loans.tanggal', '<=', $end)
            ->when($start,
                fn($q) => $q->whereBetween($payDate, [$start, $end]),
                fn($q) => $q->whereDate($payDate, '<=', $end)
            )
            ->groupBy('kasbon_loans.karyawan_id')
            ->selectRaw('kasbon_loans.karyawan_id, COALESCE(SUM(kasbon_payments.nominal),0) AS paid')
            ->pluck('paid', 'kasbon_loans.karyawan_id')
            ->toArray();

        // 2) Total dibayar s/d akhir range (untuk sisa)
        $totalPaidToEnd = \App\Models\KasbonLoan::query()
            ->leftJoin('kasbon_payments', 'kasbon_payments.kasbon_loan_id', '=', 'kasbon_loans.id')
            ->whereDate('kasbon_loans.tanggal', '<=', $end)
            ->whereDate($payDate, '<=', $end)
            ->groupBy('kasbon_loans.karyawan_id')
            ->selectRaw('kasbon_loans.karyawan_id, COALESCE(SUM(kasbon_payments.nominal),0) AS paid_end')
            ->pluck('paid_end', 'kasbon_loans.karyawan_id')
            ->toArray();

        // 3) Pokok pinjaman s/d akhir range
        $pokokByEmp = \App\Models\KasbonLoan::query()
            ->whereDate('tanggal', '<=', $end)
            ->groupBy('karyawan_id')
            ->selectRaw('karyawan_id, COALESCE(SUM(pokok),0) AS pokok')
            ->pluck('pokok', 'karyawan_id')
            ->toArray();

        // 4) Gabungkan
        $empIds = array_unique(array_merge(
            array_keys($pokokByEmp),
            array_keys($totalPaidToEnd),
            array_keys($paidInRange)
        ));

        $out = [];
        foreach ($empIds as $empId) {
            $kasbon = (int) ($paidInRange[$empId] ?? 0);
            $sisa   = max(0, (int) ($pokokByEmp[$empId] ?? 0) - (int) ($totalPaidToEnd[$empId] ?? 0));
            $out[$empId] = ['kasbon' => $kasbon, 'sisa_kasbon' => $sisa];
        }

        return $out;
    }

    public function rows()
    {
        // sesuaikan nama FK jika berbeda
        return $this->hasMany(\App\Models\RekapTransferPermataRow::class, 'rekap_transfer_permata_id');
    }
    private function buildEmpCodeToPkMap(array $rows): array
    {
        $codes = collect($rows)
            ->flatMap(fn ($r) => [ $r['id_karyawan'] ?? null, $r['no_id'] ?? null ])
            ->filter()->unique()->values();

        return \App\Models\Karyawan::whereIn('id_karyawan', $codes)
            ->pluck('id', 'id_karyawan')  // 'kode' => PK
            ->toArray();
    }

    private function resolveEmpPk(array $r, array $codeToPk, array $kasbonByEmp): ?int
    {
        $candidates = [];

        if (isset($r['no_id'])) {
            $candidates[] = (int) $r['no_id'];                   // no_id bisa PK
            if (isset($codeToPk[$r['no_id']])) {                 // atau kadang 'kode'
                $candidates[] = (int) $codeToPk[$r['no_id']];
            }
        }
        if (isset($r['id_karyawan']) && isset($codeToPk[$r['id_karyawan']])) {
            $candidates[] = (int) $codeToPk[$r['id_karyawan']];  // kode → PK
        }

        foreach ($candidates as $pk) {
            if (array_key_exists($pk, $kasbonByEmp)) return $pk;
        }
        return null;
    }

}
