<?php

namespace App\Filament\Admin\Pages;

use App\Models\RekapTransferPermata;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;

class HistoriTransferPermata extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $title           = 'Histori Transfer Permata';
    protected static ?string $navigationLabel = 'Histori Transfer Permata';
    protected static ?string $navigationGroup = 'Penggajian';
    protected static string $view             = 'filament.pages.histori-transfer-permata';

    public static function getSlug(): string
    {
        return 'histori-transfer-permata';
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    protected function getTableQuery(): Builder
    {
        return \App\Models\RekapTransferPermata::query()
            ->latest('period_start')
            ->withCount(['rows as calc_rows_count'])
            ->withSum('rows as calc_total_pembulatan',  'pembulatan')
            ->withSum('rows as calc_total_kasbon',      'kasbon')
            ->withSum('rows as calc_total_sisa_kasbon', 'sisa_kasbon')
            ->withSum('rows as calc_total_gaji_16_31',  'gaji_16_31')
            ->withSum('rows as calc_total_gaji_15_31',  'gaji_15_31')
            ->withSum('rows as calc_total_transfer',    'transfer');
    }
    protected function getTableColumns(): array
    {
        $idr = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

        // helper: gunakan nilai header kalau >0, kalau tidak pakai kalkulasi relasi
        $use = function ($primary, $fallback) {
            $p = (int) ($primary ?? 0);
            $f = (int) ($fallback ?? 0);
            return $p !== 0 ? $p : $f;
        };

        return [
            Tables\Columns\TextColumn::make('bank')
                ->label('Bank')->badge()->color('success'),

            Tables\Columns\TextColumn::make('period_start')
                ->label('Periode')
                ->getStateUsing(fn ($record) =>
                    Carbon::parse($record->period_start)->format('d M Y') . ' - ' .
                    Carbon::parse($record->period_end)->format('d M Y')
                )
                ->sortable(),

            Tables\Columns\TextColumn::make('rows_count')
                ->label('Baris')
                ->alignCenter()
                ->getStateUsing(fn ($record) =>
                    (int) ($record->rows_count ?? 0) ?: (int) ($record->calc_rows_count ?? 0)
                )
                ->sortable(),

            Tables\Columns\TextColumn::make('total_pembulatan')
                ->label('Pembulatan')->alignRight()
                ->formatStateUsing(fn ($state, $record) =>
                    $idr(($thisUse = (int) ($state ?? 0)) !== 0 ? $thisUse : (int) ($record->calc_total_pembulatan ?? 0))
                ),

            Tables\Columns\TextColumn::make('total_kasbon')
                ->label('Kasbon')->alignRight()
                ->formatStateUsing(fn ($state, $record) =>
                    $idr((int) ($state ?? 0) ?: (int) ($record->calc_total_kasbon ?? 0))
                ),

            Tables\Columns\TextColumn::make('total_sisa_kasbon')
                ->label('Sisa Kasbon')->alignRight()
                ->formatStateUsing(fn ($state, $record) =>
                    $idr((int) ($state ?? 0) ?: (int) ($record->calc_total_sisa_kasbon ?? 0))
                ),

            Tables\Columns\TextColumn::make('total_gaji_16_31')
                ->label('Gaji 16–31')->alignRight()
                ->formatStateUsing(fn ($state, $record) =>
                    $idr((int) ($state ?? 0) ?: (int) ($record->calc_total_gaji_16_31 ?? 0))
                ),

            Tables\Columns\TextColumn::make('total_gaji_15_31')
                ->label('Gaji 01–15')->alignRight()
                ->formatStateUsing(fn ($state, $record) =>
                    $idr((int) ($state ?? 0) ?: (int) ($record->calc_total_gaji_15_31 ?? 0))
                ),

            Tables\Columns\TextColumn::make('total_transfer')
                ->label('Total Transfer')->alignRight()
                ->formatStateUsing(fn ($state, $record) =>
                    $idr((int) ($state ?? 0) ?: (int) ($record->calc_total_transfer ?? 0))
                ),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('periode')
                ->form([
                    Forms\Components\DatePicker::make('from')->label('Dari'),
                    Forms\Components\DatePicker::make('to')->label('Sampai'),
                ])
                ->query(function (Builder $q, array $data) {
                    return $q
                        ->when($data['from'] ?? null, fn ($qq, $from) =>
                            $qq->whereDate('period_end', '>=', $from)
                        )
                        ->when($data['to'] ?? null, fn ($qq, $to) =>
                            $qq->whereDate('period_start', '<=', $to)
                        );
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('open')
                ->label('Buka Rekap')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn ($record) => route('filament.admin.pages.rekap-transfer-permata', [
                    'start_date' => Carbon::parse($record->period_start)->format('Y-m-d'),
                    'end_date'   => Carbon::parse($record->period_end)->format('Y-m-d'),
                ]))
                ->openUrlInNewTab(),

            Tables\Actions\Action::make('recalc')
                ->label('Recalculate Totals')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (RekapTransferPermata $record) {
                    // panggil helper di model agar konsisten
                    $record->refreshTotals();
                }),

            Tables\Actions\DeleteAction::make(),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),
        ];
    }
}
