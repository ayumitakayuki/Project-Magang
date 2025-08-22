<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\HoRekapService;
use Illuminate\Support\Arr;

class RekapTransferPermata extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Rekap Transfer Permata';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.rekap-transfer-permata';

    public ?string $start_date = null;
    public ?string $end_date   = null;
    public ?string $lokasi     = null;
    public ?string $proyek     = null;

    /** hasil dari service */
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
        $this->start_date = request('start_date') ?? now()->startOfMonth()->format('Y-m-d');
        $this->end_date   = request('end_date')   ?? now()->endOfMonth()->format('Y-m-d');
        $this->lokasi     = request('lokasi');
        $this->proyek     = request('proyek');

        // isi form dengan state saat ini
        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
            'lokasi'     => $this->lokasi,
            'proyek'     => $this->proyek,
        ]);

        $this->loadRows();
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
                            ->required(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Periode Akhir')
                            ->native(false)
                            ->required(),

                        Forms\Components\TextInput::make('lokasi')
                            ->label('Lokasi (opsional)')
                            ->placeholder('Semua lokasi'),

                        Forms\Components\TextInput::make('proyek')
                            ->label('Proyek (opsional)')
                            ->placeholder('Semua proyek'),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('apply')
                                ->label('Terapkan')
                                ->color('primary')
                                ->submit('apply-filters'),
                        ])->columnSpan(['default' => 1, 'md' => 4])->alignEnd(),
                    ]),
            ])
            ->statePath('filters'); // state disimpan di $this->filters
    }

    /** Dipanggil saat tombol "Terapkan" diklik */
    public function applyFilters(): void
    {
        $state = $this->form->getState();

        $this->start_date = Arr::get($state, 'start_date');
        $this->end_date   = Arr::get($state, 'end_date');
        $this->lokasi     = Arr::get($state, 'lokasi');
        $this->proyek     = Arr::get($state, 'proyek');

        $this->loadRows();
    }

    private function loadRows(): void
    {
        $this->rows = app(HoRekapService::class)->rekapTransferPermata(
            $this->start_date,
            $this->end_date,
            $this->lokasi,
            $this->proyek,
            'payroll'
        );
    }
}
