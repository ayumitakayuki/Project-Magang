<?php

namespace App\Filament\Admin\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\HoRekapService;
use Illuminate\Support\Arr;

class RekapGajiPeriode extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $title = 'Rekap Gaji Periode';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.rekap-gaji-periode';

    public ?string $start_date = null;
    public ?string $end_date   = null;

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

        $this->form->fill([
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
        ]);

        $this->loadRows();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()
                ->columns(['default' => 1, 'md' => 4])
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Periode Awal')->native(false)->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Periode Akhir')->native(false)->required(),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('apply')
                            ->label('Terapkan')
                            ->color('primary')
                            ->submit('apply-filters'),
                    ])->columnSpan(['default' => 1, 'md' => 4])->alignEnd(),
                ]),
        ])->statePath('filters');
    }

    public function applyFilters(): void
    {
        $state = $this->form->getState();
        $this->start_date = Arr::get($state, 'start_date');
        $this->end_date   = Arr::get($state, 'end_date');
        $this->loadRows();
    }

    private function loadRows(): void
    {
        // service kamu sebelumnya mengembalikan array terstruktur
        $this->rows = app(HoRekapService::class)->rekapGajiPeriode($this->start_date, $this->end_date);
    }
}
