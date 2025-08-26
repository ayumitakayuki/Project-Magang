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

class RekapTransferPermata extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Rekap Transfer Permata';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.rekap-transfer-permata';

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

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('apply')
                                ->label('Terapkan')
                                ->color('primary')
                                ->action('applyFilters'),   // <- sebelumnya submit('apply-filters')
                            Forms\Components\Actions\Action::make('reset')
                                ->label('Reset')
                                ->color('gray')
                                ->action(function () {
                                    $this->start_date = null;
                                    $this->end_date   = null;
                                    $this->form->fill(['start_date' => null, 'end_date' => null]);
                                    $this->loadRows();
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

        $this->loadRows();

        // Simpan hanya jika periode dipilih lengkap
        if ($this->start_date && $this->end_date) {
            $this->persistRowsToDb();
        }
    }
    private function loadRows(): void
    {
        $filterByPeriod = $this->start_date && $this->end_date;

        $this->rows = app(HoRekapService::class)->rekapTransferPermata(
            $filterByPeriod ? $this->start_date : null,
            $filterByPeriod ? $this->end_date   : null,
            null, // lokasi dihapus
            null, // proyek dihapus
            'payroll'
        );
    }
    private function persistRowsToDb(): void
    {
        // Wajib periode lengkap + ada data
        if (!$this->start_date || !$this->end_date || empty($this->rows)) {
            return;
        }

        $start = \Carbon\Carbon::parse($this->start_date);
        $end   = \Carbon\Carbon::parse($this->end_date);

        // Tentukan tipe range & label
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

        // Hitung jumlah baris dan inisialisasi variabel terkait
        $rowsCount = count($this->rows);
        $totals = []; // Inisialisasi sesuai kebutuhan aplikasi Anda
        $lokasiHeader = null; // Inisialisasi sesuai kebutuhan aplikasi Anda
        $proyekHeader = null; // Inisialisasi sesuai kebutuhan aplikasi Anda

        \Illuminate\Support\Facades\DB::transaction(function () use ($start, $end, $rangeType, $periodLabel, $rowsCount, $totals, $lokasiHeader, $proyekHeader) {

            /** @var \App\Models\RekapTransferPermata $header */
            $header = \App\Models\RekapTransferPermata::firstOrCreate([
                'bank'         => 'PERMATA',
                'period_start' => $start->toDateString(),
                'period_end'   => $end->toDateString(),
            ]);

            // sinkronkan detail
            $header->rows()->delete();

            $now = now();
            $payload = [];
            foreach (array_values($this->rows) as $i => $r) {
                $payload[] = [
                    'rekap_transfer_permata_id' => $header->id,
                    'no_urut'     => $i + 1,
                    'no_id'       => $r['no_id']   ?? null,
                    'bagian'      => $r['bagian']  ?? null,
                    'lokasi'      => $r['lokasi']  ?? null,
                    'proyek'      => $r['project'] ?? ($r['proyek'] ?? null),
                    'nama'        => $r['nama']    ?? null,

                    // >>> WAJIB: periode per-row <<<
                    'period_start' => $start->toDateString(),
                    'period_end'   => $end->toDateString(),
                    'period_label' => $periodLabel,
                    'range_type'   => $rangeType,

                    'pembulatan'  => $r['pembulatan']   ?? 0,
                    'kasbon'      => $r['kasbon']       ?? 0,
                    'sisa_kasbon' => $r['sisa_kasbon']  ?? 0,
                    'gaji_16_31'  => $r['gaji_16_31']   ?? 0,
                    'gaji_15_31'  => $r['gaji_15_31']   ?? 0,
                    'transfer'    => $r['transfer']     ?? 0,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            \Illuminate\Support\Facades\DB::table('rekap_transfer_permata_rows')->insert($payload);

            // update header (kalau kolom range_type ada di header, ikut diisi)
            $updateHeader = array_merge([
                'rows_count' => $rowsCount,
                'lokasi'     => $lokasiHeader,
                'proyek'     => $proyekHeader,
            ], $totals);

            if (Schema::hasColumn('rekap_transfer_permatas', 'range_type')) {
                $updateHeader['range_type'] = $rangeType;
            }
            $header->update($updateHeader);
        });
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
}
