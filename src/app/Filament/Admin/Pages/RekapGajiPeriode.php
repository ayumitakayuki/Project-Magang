<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Arr;
use App\Services\HoRekapService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\RekapGajiPeriod;
use Filament\Notifications\Actions\Action as NotificationAction;
use Carbon\Carbon;
use App\Exports\RekapGajiPeriodeExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapGajiPeriode extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Rekap Gaji Periode';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.rekap-gaji-periode';

    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public array $filters = [
        'start_date'     => null,
        'end_date'       => null,
        'selected_pairs' => [], // array key "Lokasi|Proyek"
    ];
    public array $pairOptions = [];
    public array $rows = [];
    public bool $isEditing = false;
    public ?int $editingId = null;

    public function mount(): void
    {
        $start = request('start_date');
        $end   = request('end_date');

        if ($rekapId = request('rekap_id')) {
            $h = RekapGajiPeriod::with('rows')->findOrFail($rekapId);

            $this->filters['start_date'] = optional($h->start_date)->format('Y-m-d');
            $this->filters['end_date']   = optional($h->end_date)->format('Y-m-d');

            // Build opsi dari DB untuk periode ini
            $this->refreshPairOptions();

            // pasangan tersimpan di DB → key "Lokasi|Proyek"
            $savedKeys = collect($h->selected_pairs ?? [])->map(function ($p) {
                $lok = $p['lokasi'] ?? 'Tanpa Lokasi';
                $prj = $p['proyek'] ?? 'Tanpa Proyek';
                return "{$lok}|{$prj}";
            })->values()->all();

            // === UNION: saved + available now (agar proyek/lokasi baru otomatis ikut) ===
            $available = array_keys($this->pairOptions);
            $this->filters['selected_pairs'] = array_values(array_unique(
                array_merge($savedKeys, $available)
            ));

            $this->form->fill($this->filters);
            $this->loadRows();
            $this->isEditing = true;
            $this->editingId = (int) $rekapId;
            return;
        }

        $this->filters['start_date'] = $start;
        $this->filters['end_date']   = $end;

        $this->refreshPairOptions();
        $this->filters['selected_pairs'] = array_keys($this->pairOptions);

        $this->form->fill($this->filters);
        $this->loadRows();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()->columns(['default' => 1, 'md' => 4])->schema([
                Forms\Components\DatePicker::make('start_date')
                    ->label('Periode Awal')
                    ->format('Y-m-d')->displayFormat('d M Y')
                    ->native(false)
                    ->reactive()->afterStateUpdated(fn () => $this->periodChanged()),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Periode Akhir')
                    ->format('Y-m-d')->displayFormat('d M Y')
                    ->native(false)
                    ->reactive()->afterStateUpdated(fn () => $this->periodChanged()),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('apply')
                        ->label('Terapkan')
                        ->color('primary')
                        ->action('applyFilters'),

                    Forms\Components\Actions\Action::make('reset')
                        ->label('Reset')
                        ->color('gray')
                        ->action(function () {
                            $this->filters['start_date'] = null;
                            $this->filters['end_date']   = null;
                            $this->form->fill(['start_date' => null, 'end_date' => null]);
                            $this->refreshPairOptions();
                            $this->loadRows();
                            \Filament\Notifications\Notification::make()
                                ->title('Filter direset')->body('Menampilkan semua data.')
                                ->success()->send();
                        }),
                ])->columnSpan(['default' => 1, 'md' => 4])->alignEnd(),
            ]),

            Forms\Components\Select::make('selected_pairs')
                ->label('Lokasi — Proyek (diambil dari Slip Gaji)')
                ->multiple()->searchable()->preload()
                ->options(fn () => $this->pairOptions)
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->filters['selected_pairs'] = $state ?? [])
                ->columnSpanFull(),
        ])->statePath('filters');
    }

    public function periodChanged(): void
    {
        $state = $this->form->getState();
        $this->filters['start_date'] = \Illuminate\Support\Arr::get($state, 'start_date', $this->filters['start_date']);
        $this->filters['end_date']   = \Illuminate\Support\Arr::get($state, 'end_date',   $this->filters['end_date']);

        $this->refreshPairOptions();

        // UNION pilihan sekarang + opsi terbaru dari DB (agar proyek/lokasi baru ikut)
        $available = array_keys($this->pairOptions);
        $current   = $this->filters['selected_pairs'] ?? [];
        $this->filters['selected_pairs'] = array_values(array_unique(
            array_merge($current, $available)
        ));

        $this->form->fill($this->filters);
        // opsional: $this->loadRows();
    }

    public function applyFilters(): void
    {
        $state = $this->form->getState();
        $start = \Illuminate\Support\Arr::get($state, 'start_date');
        $end   = \Illuminate\Support\Arr::get($state, 'end_date');

        // harus lengkap berpasangan
        if (($start && !$end) || (!$start && $end)) {
            \Filament\Notifications\Notification::make()
                ->title('Lengkapi periode')
                ->body('Isi Periode Awal dan Periode Akhir, atau kosongkan keduanya.')
                ->warning()->send();
            return;
        }

        $this->filters['start_date']     = $start;
        $this->filters['end_date']       = $end;
        $this->filters['selected_pairs'] = \Illuminate\Support\Arr::get($state, 'selected_pairs', $this->filters['selected_pairs']);

        $this->loadRows();
        $this->saveCurrentAsHeader();

        if ($this->filters['start_date'] && $this->filters['end_date']) {
            if (!empty($this->rows)) {
                $this->saveCurrentAsHeader();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Tidak ada data pada periode ini')
                    ->body('Rekap tidak disimpan karena tidak ada baris.')
                    ->warning()->send();
            }
        }
    }

    private function refreshPairOptions(): void
    {
        $start = $this->filters['start_date'];
        $end   = $this->filters['end_date'];

        $pairs = app(HoRekapService::class)->distinctPairs($start, $end);

        $this->pairOptions = collect($pairs)->mapWithKeys(function ($p) {
            $lok = $p['lokasi'] ?: 'Tanpa Lokasi';
            $prj = $p['proyek'] ?: 'Tanpa Proyek';
            return ["{$lok}|{$prj}" => "{$lok} — {$prj}"];
        })->all();

        $available = array_keys($this->pairOptions);
        $current   = $this->filters['selected_pairs'] ?? [];
        $this->filters['selected_pairs'] = array_values(array_unique(
            array_merge($current, $available)
        ));
    }
    private function loadRows(): void
    {
        $start = $this->filters['start_date'];
        $end   = $this->filters['end_date'];

        $pairs = !empty($this->filters['selected_pairs'])
        ? array_map(function ($key) {
            [$lok, $prj] = explode('|', $key, 2);
            $prj = trim($prj);
            if ($prj === '' || $prj === '-' || strcasecmp($prj, 'tanpa proyek') === 0) {
                $prj = 'Tanpa Proyek';
            }
            return ['lokasi' => $lok, 'proyek' => $prj];
        }, $this->filters['selected_pairs'])
        : null;

        $this->rows = app(\App\Services\HoRekapService::class)
            ->rekapPeriodeLaporan($start, $end, $pairs);
    }
    public function saveToDb(): void
    {
        $this->saveCurrentAsHeader();
    }
    private function saveCurrentAsHeader(): void
    {
        try {
            $start = $this->filters['start_date'];
            $end   = $this->filters['end_date'];

            if (!$start || !$end) {
                return; // hanya simpan jika periode lengkap
            }
            if (empty($this->rows)) {
                \Filament\Notifications\Notification::make()
                    ->title('Tidak ada data pada periode ini')
                    ->body('Rekap tidak disimpan karena tidak ada baris.')
                    ->warning()->send();
                return;
            }

            // Konversi selected_pairs "Lokasi|Proyek" → [['lokasi'=>..,'proyek'=>..], ...]
            $pairKeys = $this->filters['selected_pairs'] ?? [];
            $pairs = empty($pairKeys) ? null : array_map(function ($key) {
                [$lok, $prj] = explode('|', $key, 2);
                $lok = trim($lok);
                $prj = trim($prj);
                if ($prj === '' || $prj === '-' || strcasecmp($prj, 'tanpa proyek') === 0) {
                    $prj = 'Tanpa Proyek';
                }
                return ['lokasi' => $lok, 'proyek' => $prj];
            }, $pairKeys);

            $userId = Auth::id();

            // Simpan header + rows via service (sama persis seperti saveToDb sebelumnya)
            $header = app(\App\Services\HoRekapService::class)
                ->storeRekapGajiPeriode($start, $end, $pairs, $userId);

            // Set halaman ke mode editing atas hasil yang baru disimpan
            $this->isEditing = true;
            $this->editingId = (int) $header->id;

            // Notifikasi sukses
            Notification::make()
                ->title('Rekap disimpan otomatis')
                ->body(
                    'Periode ' .
                    \Carbon\Carbon::parse($header->start_date)->format('d M Y') . ' — ' .
                    \Carbon\Carbon::parse($header->end_date)->format('d M Y') .
                    ' disimpan (' . $header->rows()->count() . ' baris).'
                )
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal menyimpan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportPdf(): ?StreamedResponse
    {
        // 1) Jika sedang edit rekap tersimpan → pakai exporter by ID
        if ($this->editingId) {
            $export = new RekapGajiPeriodeExport([$this->editingId], true);

            $name = 'Rekap-Gaji-Periode-'
                . Carbon::parse($this->filters['start_date'])->format('Ymd')
                . '-'
                . Carbon::parse($this->filters['end_date'])->format('Ymd')
                . '.pdf';

            return $export->download($name);
        }

        // 2) Belum disimpan → buat PDF on-the-fly dari state/rows saat ini
        $start = $this->filters['start_date'];
        $end   = $this->filters['end_date'];

        // Konversi selected_pairs ("lokasi|proyek") → [['lokasi'=>..,'proyek'=>..], ...]
        $pairKeys = $this->filters['selected_pairs'] ?? [];
        $pairs = empty($pairKeys) ? null : array_map(function ($key) {
            [$lok, $prj] = explode('|', $key, 2);
            $lok = trim($lok);
            $prj = trim($prj);
            if ($prj === '' || $prj === '-' || strcasecmp($prj, 'tanpa proyek') === 0) {
                $prj = 'Tanpa Proyek';
            }
            return ['lokasi' => $lok, 'proyek' => $prj];
        }, $pairKeys);

        // Ambil rows agregasi sama seperti di tampilan
        $rows = app(\App\Services\HoRekapService::class)
            ->rekapPeriodeLaporan($start, $end, $pairs);

        // Siapkan "rekap" sederhana untuk dipakai di blade PDF
        $rekap = (object) [
            'start_date'     => Carbon::parse($start),
            'end_date'       => Carbon::parse($end),
            'selected_pairs' => $pairs,
            'user'           => \Illuminate\Support\Facades\Auth::user(),
        ];

        // Render blade yang sama dengan exporter
        $html = View::make('exports.rekap-gaji-periode-pdf', [
            'rekap' => $rekap,
            'rows'  => $rows,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $name = 'Rekap-Gaji-Periode-'
            . Carbon::parse($start)->format('Ymd')
            . '-'
            . Carbon::parse($end)->format('Ymd')
            . '.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $name);
    }
}
