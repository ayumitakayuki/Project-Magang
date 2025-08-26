<?php

namespace App\Filament\Admin\Pages;

use App\Models\RekapGajiPeriod;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\BulkAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RekapGajiPeriodeExport;
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

    protected function getTableColumns(): array
    {
        return [
            // Periode: pakai field start_date agar ada datanya & bisa di-sort
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

            // Aksi: hanya Lihat & Edit (PDF/Excel dihapus dari sini)
            Tables\Columns\TextColumn::make('aksi')
                ->label('Aksi')
                ->html()
                ->getStateUsing(function ($record) {
                    $lihatUrl = route('filament.admin.pages.detail-rekap-gaji-periode', ['id' => $record->id]);
                    $editUrl  = route('filament.admin.pages.rekap-gaji-periode', ['rekap_id' => $record->id]);

                    return <<<HTML
                        <div class="flex items-center justify-center gap-3">
                            <a href="{$lihatUrl}" class="text-blue-600 hover:underline">Lihat</a>
                            <a href="{$editUrl}"  class="text-orange-600 hover:underline">Edit</a>
                        </div>
                    HTML;
                })
                ->alignCenter(),
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

    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),

            // Export Excel massal (opsional)
            BulkAction::make('export_excel_massal')
                ->label('Export Excel (Massal)')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (\Illuminate\Support\Collection $records) {
                    $ids = $records->pluck('id')->toArray();
                    $filename = 'Rekap-Gaji-Periode-' . now()->format('Ymd_His') . '.xlsx';
                    return Excel::download(new RekapGajiPeriodeExport($ids, true), $filename);
                }),
        ];
    }
}
