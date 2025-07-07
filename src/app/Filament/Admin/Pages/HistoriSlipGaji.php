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
            Tables\Columns\TextColumn::make('aksi')
                ->label('Aksi')
                ->html()
                ->getStateUsing(function ($record) {
                    $lihatUrl = route('filament.admin.pages.detail-slip-gaji', ['id' => $record->id]);
                    $editUrl = route('filament.admin.pages.slip-gaji-hitung', ['id' => $record->id]);

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
                    Karyawan::query()->distinct()->pluck('lokasi', 'lokasi')->toArray()
                )
                ->searchable(),

            SelectFilter::make('jenis_proyek')
                ->label('Proyek')
                ->options(
                    Karyawan::query()->distinct()->pluck('jenis_proyek', 'jenis_proyek')->toArray()
                )
                ->searchable(),
        ];
    }
}
