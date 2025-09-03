<?php

namespace App\Filament\Admin\Pages;

use App\Models\Gaji;
use App\Models\Karyawan;
use Filament\Pages\Page;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SlipGajiExport;

class HistoriSlipGaji extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.histori-slip-gaji';
    protected static ?string $title = 'Histori Slip Gaji';

    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getTableQuery(): Builder
    {
        return Gaji::query()
            ->with(['details', 'karyawan'])
            ->latest('periode_awal');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id_karyawan')->label('ID Karyawan')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            Tables\Columns\TextColumn::make('lokasi')->label('Lokasi'),
            Tables\Columns\TextColumn::make('jenis_proyek')->label('Proyek'),
            Tables\Columns\TextColumn::make('periode_awal')->label('Periode')
                ->formatStateUsing(fn ($state, $record) =>
                    \Carbon\Carbon::parse($state)->format('d M') . ' - ' .
                    \Carbon\Carbon::parse($record->periode_akhir)->format('d M Y')
                ),
            Tables\Columns\TextColumn::make('subtotal')
                ->label('Total Gaji')
                ->alignRight()
                ->getStateUsing(fn ($record) =>
                    'Rp ' . number_format(optional($record->details->where('kode', 'jml')->first())->total ?? 0, 0, ',', '.')
                ),
            Tables\Columns\TextColumn::make('kasbon')
                ->label('Kasbon')
                ->alignRight()
                ->getStateUsing(fn ($record) =>
                    'Rp ' . number_format(optional($record->details->where('kode', 'h')->first())->total ?? 0, 0, ',', '.')
                ),
            Tables\Columns\TextColumn::make('grand_total')
                ->label('Grand Total')
                ->alignRight()
                ->color('success')
                ->weight('bold')
                ->getStateUsing(fn ($record) =>
                    'Rp ' . number_format(optional($record->details->where('kode', 'grand')->first())->total ?? 0, 0, ',', '.')
                ),
            Tables\Columns\TextColumn::make('tipe_pembayaran')
                ->label('Tipe')
                ->badge()
                ->sortable()
                ->searchable()
                ->formatStateUsing(fn ($state) => $state ? ucwords(str_replace('-', ' ', $state)) : '-')
                ->color(fn ($state) => match ($state) {
                    'payroll'      => 'success',
                    'non-payroll'  => 'warning',
                    default        => 'gray',
                }),
            Tables\Columns\TextColumn::make('aksi')
                ->label('Aksi')
                ->html()
                ->getStateUsing(function ($record) {
                    $lihatUrl = route('filament.admin.pages.detail-slip-gaji', ['id' => $record->id]);
                    $editUrl = route('filament.admin.pages.slip-gaji-hitung', [
                        'id' => $record->id,
                        'karyawan_id' => $record->id_karyawan,
                        'start_date' => \Carbon\Carbon::parse($record->periode_awal)->format('Y-m-d'),
                        'end_date' => \Carbon\Carbon::parse($record->periode_akhir)->format('Y-m-d'),
                    ]);

                    return <<<HTML
                        <div class="flex items-center justify-center gap-3">
                            <a href="{$lihatUrl}" class="text-blue-600 hover:underline">Lihat</a>
                            <a href="{$editUrl}" class="text-orange-600 hover:underline">Edit</a>
                        </div>
                    HTML;
                })

                ->alignCenter(),



        ];
    }
    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'harian lepas' => 'Harian Lepas',
                    'kontrak' => 'Kontrak',
                    'tetap' => 'Tetap',
                ])
                ->searchable(),

            SelectFilter::make('lokasi')
                ->label('Lokasi')
                ->options(
                    Karyawan::query()->whereNotNull('lokasi')->distinct()->pluck('lokasi', 'lokasi')->toArray()
                )
                ->searchable(),

            SelectFilter::make('jenis_proyek')
                ->label('Proyek')
                ->options(
                    Karyawan::query()->whereNotNull('jenis_proyek')->distinct()->pluck('jenis_proyek', 'jenis_proyek')->toArray()
                )
                ->searchable(),
            SelectFilter::make('tipe_pembayaran')
                ->label('Tipe Pembayaran')
                ->options([
                    'payroll'     => 'Payroll',
                    'non-payroll' => 'Non Payroll',
                ]),
        ];
    }
    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),

            BulkAction::make('cetak_massal')
                ->label('Cetak Massal (PDF)')
                ->icon('heroicon-o-printer')
                ->action(function (Collection $records) {
                    $data = [
                        'gajis' => $records,
                    ];

                    $pdf = Pdf::loadView('exports.slip-gaji-massal-pdf', $data)->setPaper('a4', 'portrait');

                    $filename = 'Slip-Gaji-Massal-pdf-' . now()->format('Ymd_His') . '.pdf';
                    $path = storage_path('app/public/' . $filename);
                    $pdf->save($path);

                    return response()->download($path)->deleteFileAfterSend(true);
                }),
            BulkAction::make('export_excel_massal')
                ->label('Export Excel Massal')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Collection $records) {
                    $ids = $records->pluck('id')->toArray();
                    $filename = 'Slip-Gaji-Massal-' . now()->format('Ymd_His') . '.xlsx';

                    return Excel::download(new SlipGajiExport($ids), $filename);
                }),
        ];
    }
}
