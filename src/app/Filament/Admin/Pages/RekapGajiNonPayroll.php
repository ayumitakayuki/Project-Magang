<?php

namespace App\Filament\Admin\Pages;

use App\Exports\RekapGajiNonPayrollExport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use App\Services\HoRekapService;
use App\Models\RekapGajiNonPayroll as RekapHeader;
use App\Models\RekapGajiNonPayrollRow as RekapRow;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Gaji;


class RekapGajiNonPayroll extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Rekap Gaji Non Payroll';
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static string $view = 'filament.pages.rekap-gaji-nonpayroll';

    public ?string $start_date = null;
    public ?string $end_date   = null;

    /** wajib karena pakai ->statePath('filters') */
    public array $filters = [];
    public array $rows = [];

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

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->loadRows();

        // auto-simpan bila periode lengkap saat open via query param
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
                            ->native(false)
                            ->format('Y-m-d'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Periode Akhir')
                            ->native(false)
                            ->format('Y-m-d'),

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
                                    Notification::make()
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

        // Harus isi keduanya atau kosongkan keduanya (sama seperti Rekap Permata)
        if (($start && !$end) || (!$start && $end)) {
            Notification::make()
                ->title('Lengkapi periode')
                ->body('Isi Periode Awal dan Periode Akhir, atau kosongkan keduanya.')
                ->warning()
                ->send();
            return;
        }

        $this->start_date = $start;
        $this->end_date   = $end;

        $this->loadRows();

        // Simpan jika periode lengkap
        if ($this->start_date && $this->end_date) {
            $this->persistRowsToDb();
        }
    }
    private function loadRows(): void
    {
        $filterByPeriod = $this->start_date && $this->end_date;

        $this->rows = app(\App\Services\HoRekapService::class)->rekapNonPayroll(
            $filterByPeriod ? $this->start_date : null,
            $filterByPeriod ? $this->end_date   : null,
        );

        if (empty($this->rows)) return;

        // GRAND total slip → pembulatan (+ / -)
        $grandByEmp = $this->getSlipGrandTotals($this->start_date, $this->end_date);

        // Kasbon per-periode (INCLUSIVE) + sisa per akhir periode
        $kasbonByEmp = $this->getKasbonStatsForRange($this->start_date, $this->end_date);
        $codeToPk    = $this->buildEmpCodeToPkMap($this->rows);

        $this->rows = collect($this->rows)->map(function ($r) use ($grandByEmp, $kasbonByEmp, $codeToPk) {
            // ---- pembulatan dari GRAND slip
            $candidates = [ $r['id_karyawan'] ?? null, $r['no_id'] ?? null ];
            $grand = 0;
            foreach ($candidates as $empCode) {
                if ($empCode !== null && array_key_exists($empCode, $grandByEmp)) {
                    $grand = (int) $grandByEmp[$empCode];
                    break;
                }
            }
            $r['pembulatan'] = $grand;
            $r['plus']       = $grand >= 0 ? '+' : '-';

            // ---- kasbon & sisa_kasbon sesuai PERIODE
            $empPk = $this->resolveEmpPk($r, $codeToPk, $kasbonByEmp);
            $stats = $empPk !== null
                ? ($kasbonByEmp[$empPk] ?? ['kasbon' => 0, 'sisa_kasbon' => 0])
                : ['kasbon' => 0, 'sisa_kasbon' => 0];

            $r['kasbon']      = (int) $stats['kasbon'];        // total bayar di range
            $r['sisa_kasbon'] = (int) $stats['sisa_kasbon'];   // sisa per akhir range

            // ---- turunan: total setelah bon (basis = pembulatan / total_slip)
            $basis = (int) ($r['pembulatan'] ?? ($r['total_slip'] ?? 0));
            $r['total_setelah_bon'] = $basis - (int) $r['kasbon'];

            return $r;
        })->values()->all();
    }

    private function persistRowsToDb(): void
    {
        if (!$this->start_date || !$this->end_date) {
            return;
        }

        if (empty($this->rows)) {
            Notification::make()
                ->title('Tidak ada data pada periode ini')
                ->body('Rekap tidak disimpan karena tidak ada baris.')
                ->warning()
                ->send();
            return;
        }

        $start = Carbon::parse($this->start_date);
        $end   = Carbon::parse($this->end_date);

        // Tentukan tipe range & label (01–15 / 16–akhir / custom)
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

        DB::transaction(function () use ($start, $end, $rangeType, $periodLabel) {
            /** @var \App\Models\RekapGajiNonPayroll $header */
            $header = RekapHeader::firstOrCreate(
                [
                    'period_start' => $start->toDateString(),
                    'period_end'   => $end->toDateString(),
                ],
                [
                    'period_label' => $periodLabel,
                    'range_type'   => $rangeType,
                ]
            );

            // Sinkronkan detail
            $header->rows()->delete();

            $now = now();
            $payload = [];
            foreach (array_values($this->rows) as $i => $r) {
                $payload[] = [
                    'rekap_gaji_non_payroll_id' => $header->id,
                    'no_urut'        => $i + 1,
                    'no_id'          => $r['no_id']   ?? null,
                    'bagian'         => $r['bagian']  ?? null,
                    'lokasi'         => $r['lokasi']  ?? null,
                    'project'        => $r['project'] ?? ($r['proyek'] ?? null),
                    'nama'           => $r['nama']    ?? null,
                    'cd'             => $r['cd']      ?? null,
                    'plus'           => $r['plus']    ?? '+',

                    // uang
                    'pembulatan'        => (int) ($r['pembulatan']        ?? 0), // dari GRAND total
                    'kasbon'            => (int) ($r['kasbon']            ?? 0),
                    'sisa_kasbon'       => (int) ($r['sisa_kasbon']       ?? 0),
                    'total_setelah_bon' => (int) ($r['total_setelah_bon'] ?? ($r['total_slip'] ?? 0)),
                    'total_slip'        => (int) ($r['total_slip']        ?? 0),
                    'subtotal'          => (int) ($r['subtotal']          ?? 0),

                    // jejak periode per-row
                    'period_start'   => $start->toDateString(),
                    'period_end'     => $end->toDateString(),
                    'period_label'   => $periodLabel,
                    'range_type'     => $rangeType,

                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            RekapRow::insert($payload);

            // hitung total & rows_count ke header
            $header->refreshTotals();
        });

        Notification::make()
            ->title('Rekap tersimpan')
            ->body("Data Non-Payroll periode {$periodLabel} berhasil disimpan.")
            ->success()
            ->send();
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

        // Pastikan data periode ini sudah tersimpan (pakai persistRowsToDb jika ada)
        if (method_exists($this, 'persistRowsToDb')) {
            $this->persistRowsToDb();
        }

        $header = RekapHeader::query()
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

        return (new RekapGajiNonPayrollExport([$header->id]))->download();
    }
    private function getSlipGrandTotals(?string $startDate, ?string $endDate): array
    {
        $query = Gaji::query()
            ->with(['details' => function ($q) {
                $q->where('kode', 'grand');
            }]);

        if ($startDate && $endDate) {
            $query->whereDate('periode_awal', '>=', $startDate)
                ->whereDate('periode_akhir', '<=', $endDate);
        }

        $slips = $query->get();

        return $slips
            ->groupBy('id_karyawan')
            ->map(function ($list) {
                // pilih slip terbaru per karyawan
                $latest = $list->sortByDesc('periode_akhir')->first();
                return (int) optional(optional($latest)->details->first())->total ?: 0;
            })
            ->toArray();
    }
    private function getKasbonStatsForRange(?string $startDate, ?string $endDate): array
    {
        // end default = hari ini kalau periode kosong
        $end   = $endDate   ? \Carbon\Carbon::parse($endDate)->toDateString()   : now()->toDateString();
        $start = $startDate ? \Carbon\Carbon::parse($startDate)->toDateString() : null;

        $payDate = DB::raw("DATE(COALESCE(kasbon_payments.tanggal, kasbon_payments.created_at))");

        // 1) Jumlah pembayaran DI DALAM range (kalau start null → semua s/d end)
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

        // 2) Total dibayar s/d akhir range → untuk sisa
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
            $candidates[] = (int) $r['no_id'];                   // kalau no_id sudah PK
            if (isset($codeToPk[$r['no_id']])) {                 // atau ternyata kode
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
