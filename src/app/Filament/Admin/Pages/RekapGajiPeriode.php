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
        // default periode
        $start = request('start_date') ?? now()->startOfMonth()->format('Y-m-d');
        $end   = request('end_date')   ?? now()->endOfMonth()->format('Y-m-d');

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
                    ->native(false)->required()
                    ->reactive()->afterStateUpdated(fn () => $this->periodChanged()),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Periode Akhir')
                    ->format('Y-m-d')->displayFormat('d M Y')
                    ->native(false)->required()
                    ->reactive()->afterStateUpdated(fn () => $this->periodChanged()),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('apply')
                        ->label('Terapkan')->color('primary')->action('applyFilters'),
                    Forms\Components\Actions\Action::make('save')
                        ->label('Simpan ke Database')->icon('heroicon-o-arrow-down-tray')
                        ->color('success')->requiresConfirmation()
                        ->disabled(fn () => empty($this->rows))
                        ->action('saveToDb'),
                ])->columnSpan(['default' => 1, 'md' => 4])->alignEnd(),
            ]),

            Forms\Components\Select::make('selected_pairs')
                ->label('Lokasi — Proyek (diambil dari Slip Gaji)')
                ->multiple()->searchable()->preload()
                ->options(fn () => $this->pairOptions)
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->filters['selected_pairs'] = $state ?? [];
                    // $this->loadRows(); // jika mau auto-refresh tabel
                })
                ->columnSpanFull(),
        ])->statePath('filters');   // ⬅️ tambahkan ini
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

        $this->filters['start_date']     = \Illuminate\Support\Arr::get($state, 'start_date', $this->filters['start_date']);
        $this->filters['end_date']       = \Illuminate\Support\Arr::get($state, 'end_date',   $this->filters['end_date']);
        $this->filters['selected_pairs'] = \Illuminate\Support\Arr::get($state, 'selected_pairs', $this->filters['selected_pairs']);

        $this->loadRows();
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
        $state = $this->form->getState();

        $start = Arr::get($state, 'start_date', $this->filters['start_date']);
        $end   = Arr::get($state, 'end_date',   $this->filters['end_date']);

        // konversi selected_pairs ("lokasi|proyek") -> [['lokasi'=>..,'proyek'=>..], ...]
        $pairKeys = Arr::get($state, 'selected_pairs', $this->filters['selected_pairs']);
        $pairs = empty($pairKeys) ? null : array_map(function ($key) {
            [$lok, $prj] = explode('|', $key, 2);
            return ['lokasi' => $lok, 'proyek' => $prj];
        }, $pairKeys);

        $userId = Auth::id(); // atau Auth::guard(config('filament.auth.guard'))->id();
        $header = app(\App\Services\HoRekapService::class)
            ->storeRekapGajiPeriode($start, $end, $pairs, $userId);

        Notification::make()
            ->title('Rekap tersimpan')
            ->body(
                'Periode ' .
                \Carbon\Carbon::parse($header->start_date)->format('d M Y') . ' — ' .
                \Carbon\Carbon::parse($header->end_date)->format('d M Y') .
                ' berhasil disimpan (' . $header->rows()->count() . ' baris).'
            )
            ->success()
            ->send();
    }
    
}
