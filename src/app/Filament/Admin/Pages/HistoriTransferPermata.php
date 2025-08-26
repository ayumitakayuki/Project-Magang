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
use App\Exports\RekapTransferPermataExport;
use Filament\Notifications\Notification;


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

    /** TABLE */
    protected function getTableQuery(): Builder
    {
        return RekapTransferPermata::query()->latest('period_start');
    }

    protected function getTableColumns(): array
    {
        $idr = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');

        return [
            Tables\Columns\TextColumn::make('bank')
                ->label('Bank')->badge()->color('success'),

            Tables\Columns\TextColumn::make('period_start')
                ->label('Periode')
                ->getStateUsing(fn ($record) =>
                    \Carbon\Carbon::parse($record->period_start)->format('d M Y') . ' - ' .
                    \Carbon\Carbon::parse($record->period_end)->format('d M Y')
                )
                ->sortable(),

            // Tables\Columns\TextColumn::make('lokasi')->label('Lokasi')->default('-'),
            // Tables\Columns\TextColumn::make('proyek')->label('Proyek')->default('-'),
            Tables\Columns\TextColumn::make('rows_count')->label('Baris')->alignCenter()->sortable(),

            // pakai $state untuk format rupiah
            Tables\Columns\TextColumn::make('total_pembulatan')
                ->label('Pembulatan')->alignRight()
                ->formatStateUsing(fn ($state) => $idr($state)),

            Tables\Columns\TextColumn::make('total_kasbon')
                ->label('Kasbon')->alignRight()
                ->formatStateUsing(fn ($state) => $idr($state)),

            Tables\Columns\TextColumn::make('total_sisa_kasbon')
                ->label('Sisa Kasbon')->alignRight()
                ->formatStateUsing(fn ($state) => $idr($state)),

            Tables\Columns\TextColumn::make('total_gaji_16_31')
                ->label('Gaji 16–31')->alignRight()
                ->formatStateUsing(fn ($state) => $idr($state)),

            Tables\Columns\TextColumn::make('total_gaji_15_31')
                ->label('Gaji 01–15')->alignRight()
                ->formatStateUsing(fn ($state) => $idr($state)),

            Tables\Columns\TextColumn::make('total_transfer')
                ->label('Total Transfer')->alignRight()
                ->formatStateUsing(fn ($state) => $idr($state)),
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
                    'start_date' => \Carbon\Carbon::parse($record->period_start)->format('Y-m-d'),
                    'end_date'   => \Carbon\Carbon::parse($record->period_end)->format('Y-m-d'),
                ]))
                ->openUrlInNewTab(),

            Tables\Actions\Action::make('recalc')
                ->label('Recalculate Totals')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (RekapTransferPermata $record) {
                    $agg = $record->rows()->selectRaw("
                        COUNT(*) as rows_count,
                        COALESCE(SUM(pembulatan),0)    as total_pembulatan,
                        COALESCE(SUM(kasbon),0)        as total_kasbon,
                        COALESCE(SUM(sisa_kasbon),0)   as total_sisa_kasbon,
                        COALESCE(SUM(gaji_16_31),0)    as total_gaji_16_31,
                        COALESCE(SUM(gaji_15_31),0)    as total_gaji_15_31,
                        COALESCE(SUM(transfer),0)      as total_transfer
                    ")->first();
                    $record->update($agg->toArray());
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
