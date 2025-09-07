<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use App\Models\AbsensiRekap;

class SlipGaji extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Slip Gaji';
    protected static ?string $title = 'Slip Gaji';
    protected static string $view = 'filament.pages.slip-gaji';

    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }

    // ✅ Kembalikan fungsi query default: tampilkan semua karyawan yang punya rekap
    protected function getTableQuery(): Builder
    {
        return Karyawan::query()
            ->whereHas('rekaps')
            ->orderBy('id', 'asc');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id_karyawan')->label('ID Karyawan')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color('primary'),
            Tables\Columns\TextColumn::make('lokasi')->label('Lokasi'),
            Tables\Columns\TextColumn::make('jenis_proyek')->label('Proyek'),
            Tables\Columns\TextColumn::make('aksi')
                ->label('Aksi')
                ->html()
                ->getStateUsing(fn ($record) =>
                    '<a href="' . route('filament.admin.pages.slip-gaji-hitung', [
                        'karyawan_id' => $record->id_karyawan,
                    ]) . '" class="text-blue-600 hover:underline">Buat Slip</a>'
                )
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
                    Karyawan::query()
                        ->whereNotNull('lokasi')
                        ->distinct()
                        ->pluck('lokasi', 'lokasi')
                        ->toArray()
                )
                ->searchable(),

            SelectFilter::make('jenis_proyek')
                ->label('Proyek')
                ->options(
                    Karyawan::query()
                        ->whereNotNull('jenis_proyek')
                        ->distinct()
                        ->pluck('jenis_proyek', 'jenis_proyek')
                        ->toArray()
                )
                ->searchable(), 
        ];
    }
}
