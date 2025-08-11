<?php

namespace App\Filament\Admin\Pages;

use App\Models\AbsensiRekap;
use App\Models\Karyawan;
use Filament\Pages\Page;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RekapAbsensiExport;
use App\Exports\AbsensiExport;

class HistoriRekapAbsensi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.histori-rekap-absensi';
    protected static ?string $title = 'Histori Rekap Absensi';

    public static function getNavigationGroup(): ?string
    {
        return 'Absensi';
    }

    protected function getTableQuery(): Builder
    {
        return AbsensiRekap::query()
            ->with('karyawan')
            ->latest('periode_awal');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('karyawan_id')->label('ID Karyawan')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('periode_awal')->label('Periode')
                ->formatStateUsing(fn ($state, $record) =>
                    \Carbon\Carbon::parse($state)->format('d M') . ' - ' .
                    \Carbon\Carbon::parse($record->periode_akhir)->format('d M Y')
                ),
            Tables\Columns\TextColumn::make('sj')
                ->label('SJ')
                ->formatStateUsing(fn($state) => fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)),

            Tables\Columns\TextColumn::make('sabtu')
                ->label('Sabtu')
                ->formatStateUsing(fn($state) => fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)),

            Tables\Columns\TextColumn::make('minggu')
                ->label('Minggu')
                ->formatStateUsing(fn($state) => fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)),

            Tables\Columns\TextColumn::make('hari_besar')
                ->label('Hari Besar')
                ->formatStateUsing(fn($state) => fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)),

            Tables\Columns\TextColumn::make('tidak_masuk')
                ->label('Tidak Masuk')
                ->formatStateUsing(fn($state) => fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)),

            Tables\Columns\TextColumn::make('sisa_jam')
                ->label('Sisa Jam')
                ->formatStateUsing(fn($state) => fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)),

            Tables\Columns\TextColumn::make('total_jam')
                ->label('Total Jam')
                ->formatStateUsing(fn($state) => fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)),
            Tables\Columns\TextColumn::make('jumlah_hari')
                ->label('Jumlah Hari')
                ->sortable()
                ->formatStateUsing(fn($state) => (fmod($state, 1) === 0.0 ? (int) $state : number_format($state, 1)) . ' hari'),
        ];
    }
    protected function getTableBulkActions(): array
    {
        return [
            BulkAction::make('hapus_rekap')
                ->label('Hapus')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function ($records) {
                    $jumlah = $records->count();

                    foreach ($records as $record) {
                        $record->delete();
                    }

                    Notification::make()
                        ->title('Data Dihapus')
                        ->body("Berhasil menghapus $jumlah rekap absensi.")
                        ->success()
                        ->send();
                }),
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
        ];
    }
}