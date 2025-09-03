<?php

namespace App\Filament\Admin\Pages;

use App\Models\RekapGajiPeriod;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;

class HistoriRekapGajiPeriode extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string $view = 'filament.pages.histori-rekap-gaji-periode';
    protected static ?string $title = 'Histori Rekap Gaji';

    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }

    protected function getTableQuery(): Builder
    {
        return RekapGajiPeriod::query()
            ->withCount('rows')
            ->latest('start_date');
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('start_date')
                ->label('Periode')
                ->sortable()
                ->formatStateUsing(fn ($state, $record) =>
                    ($record->start_date?->format('d M Y') ?? '-') . ' - ' .
                    ($record->end_date?->format('d M Y') ?? '-')
                ),
            Tables\Columns\TextColumn::make('rows_count')
                ->label('Baris')
                ->badge(),

            Tables\Columns\TextColumn::make('total_payroll')
                ->label('Total Payroll')
                ->alignRight()
                ->getStateUsing(fn ($record) => 'Rp ' . number_format($record->total_payroll ?? 0, 0, ',', '.')),

            Tables\Columns\TextColumn::make('total_non_payroll')
                ->label('Total Non Payroll')
                ->alignRight()
                ->getStateUsing(fn ($record) => 'Rp ' . number_format($record->total_non_payroll ?? 0, 0, ',', '.')),

            Tables\Columns\TextColumn::make('total_grand')
                ->label('Grand Total')
                ->alignRight()
                ->color('success')->weight('bold')
                ->getStateUsing(fn ($record) => 'Rp ' . number_format($record->total_grand ?? 0, 0, ',', '.')),

            Tables\Columns\TextColumn::make('created_by')
                ->label('Dibuat oleh')
                ->getStateUsing(fn ($record) => optional($record->user)->name ?? '-'),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Dibuat')
                ->dateTime('d M Y H:i'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Filter::make('periode')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start')
                        ->label('Dari')->native(false)->format('Y-m-d'),
                    \Filament\Forms\Components\DatePicker::make('end')
                        ->label('Sampai')->native(false)->format('Y-m-d'),
                ])
                ->query(function (Builder $query, array $data) {
                    return $query
                        ->when($data['start'] ?? null, fn ($q, $d) => $q->whereDate('start_date', '>=', $d))
                        ->when($data['end'] ?? null, fn ($q, $d) => $q->whereDate('end_date', '<=', $d));
                }),
        ];
    }
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('open')
                ->label('Buka Rekap')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (\App\Models\RekapGajiPeriod $record) =>
                    \App\Filament\Admin\Pages\RekapGajiPeriode::getUrl(['rekap_id' => $record->id])
                )
                ->openUrlInNewTab(),
        ];
    }
    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make()
                ->label('Hapus Terpilih')
                ->requiresConfirmation(),
        ];
    }

}
