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
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Actions;

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
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getTableQuery(): Builder
    {
        return AbsensiRekap::query()
            ->with('karyawan')
            ->orderBy('karyawan_id', 'asc')
            ->latest('periode_awal');
    }

    protected function getTableColumns(): array
    {
        return [ 
            Tables\Columns\TextColumn::make('karyawan.id_karyawan')
                ->label('ID Karyawan')
                ->placeholder('-')
                ->searchable()
                ->sortable(
                    query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            Karyawan::select('id_karyawan')
                                ->whereColumn('karyawans.id', 'absensi_rekaps.karyawan_id'),
                            $direction
                        );
                    }
                ),

            Tables\Columns\TextColumn::make('karyawan.nama')
                ->label('Nama')
                ->placeholder('-')
                ->searchable()
                ->sortable(
                    query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            Karyawan::select('nama')
                                ->whereColumn('karyawans.id', 'absensi_rekaps.karyawan_id'),
                            $direction
                        );
                    }
                ),

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
                    'staff' => 'Staff',
                    'harian tetap' => 'Harian Tetap',
                    'harian lepas' => 'Harian Lepas',
                ])
                ->searchable()
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['value'])) {
                        $query->whereHas('karyawan', fn ($k) => $k->where('status', $data['value']));
                    }
                }),

            SelectFilter::make('lokasi')
                ->label('Lokasi')
                ->options(['workshop' => 'workshop', 'proyek' => 'proyek'])
                ->searchable()
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['value'])) {
                        $query->whereHas('karyawan', fn ($k) => $k->where('lokasi', $data['value']));
                    }
                }),

            SelectFilter::make('jenis_proyek')
                ->label('Proyek')
                ->options(
                    Karyawan::query()
                        ->whereNotNull('jenis_proyek')
                        ->distinct()
                        ->pluck('jenis_proyek', 'jenis_proyek')
                        ->toArray()
                )
                ->searchable()
                ->query(function (Builder $query, array $data) {
                    if (!empty($data['value'])) {
                        $query->whereHas('karyawan', fn ($k) => $k->where('jenis_proyek', $data['value']));
                    }
                }),
        ];
    }
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('edit_rekap')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading(fn ($record) => 'Edit Rekap: ' . ($record->karyawan->nama ?? '-'))
                ->form([
                    Forms\Components\Grid::make(6)->schema([
                        TextInput::make('sj')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2)->required(),
                        TextInput::make('sabtu')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2)->required(),
                        TextInput::make('minggu')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2)->required(),
                        TextInput::make('hari_besar')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2)->required(),
                        TextInput::make('tidak_masuk')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2)->required(),
                        TextInput::make('sisa_jam')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2)->required(),
                        // opsional: kolom sisa_* lain kalau mau diedit juga
                        TextInput::make('jumlah_hari')->numeric()->minValue(0)->step(0.5)->suffix(' hari')->columnSpan(2),
                    ]),
                ])
                ->fillForm(fn (AbsensiRekap $record) => [
                    'sj'           => (float) $record->sj,
                    'sabtu'        => (float) $record->sabtu,
                    'minggu'       => (float) $record->minggu,
                    'hari_besar'   => (float) $record->hari_besar,
                    'tidak_masuk'  => (float) $record->tidak_masuk,
                    'sisa_jam'     => (float) $record->sisa_jam,
                    'jumlah_hari'  => (float) $record->jumlah_hari,
                ])
                ->action(function (AbsensiRekap $record, array $data) {
                    // hitung ulang total_jam sesuai rumus service
                    $total = max(0, 
                        (float)($data['sj'] ?? 0)
                    + (float)($data['sabtu'] ?? 0)
                    + (float)($data['minggu'] ?? 0)
                    + (float)($data['hari_besar'] ?? 0)
                    - (float)($data['tidak_masuk'] ?? 0)
                    - (float)($data['sisa_jam'] ?? 0)
                    );

                    $record->update([
                        'sj'           => $data['sj'],
                        'sabtu'        => $data['sabtu'],
                        'minggu'       => $data['minggu'],
                        'hari_besar'   => $data['hari_besar'],
                        'tidak_masuk'  => $data['tidak_masuk'],
                        'sisa_jam'     => $data['sisa_jam'],
                        'jumlah_hari'  => $data['jumlah_hari'] ?? $record->jumlah_hari,
                        'total_jam'    => $total,
                    ]);

                    Notification::make()
                        ->title('Rekap diperbarui')
                        ->body('Rekap ' . ($record->karyawan->nama ?? '-') . ' berhasil disimpan.')
                        ->success()->send();
                })
                ->closeModalByClickingAway(false),
        ];
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit_karyawan_terbaru')
                ->label('Edit Rekap Karyawan (terbaru)')
                ->icon('heroicon-o-user')
                ->form([
                    Forms\Components\Select::make('karyawan_id')
                        ->label('Karyawan')
                        ->options(
                            Karyawan::orderBy('nama')->pluck('nama', 'id')->toArray()
                        )
                        ->searchable()
                        ->required(),
                    // field-field rekap muncul setelah pilih karyawan (lazy fill)
                    Forms\Components\Grid::make(6)->schema([
                        TextInput::make('sj')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2),
                        TextInput::make('sabtu')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2),
                        TextInput::make('minggu')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2),
                        TextInput::make('hari_besar')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2),
                        TextInput::make('tidak_masuk')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2),
                        TextInput::make('sisa_jam')->numeric()->minValue(0)->step(0.5)->suffix(' jam')->columnSpan(2),
                        TextInput::make('jumlah_hari')->numeric()->minValue(0)->step(0.5)->suffix(' hari')->columnSpan(2),
                    ]),
                ])
                ->mountUsing(function (Tables\Actions\Action $action, array $data) {
                    // biarkan kosong dulu saat modal dibuka
                    $action->fillForm([
                        'sj' => null,
                    ]);
                })
                ->afterFormValidated(function (Tables\Actions\Action $action, array $data) {
                    // ketika karyawan dipilih, isi form dengan rekap TERBARU miliknya
                    if (!empty($data['karyawan_id'])) {
                        $rec = AbsensiRekap::where('karyawan_id', $data['karyawan_id'])
                            ->latest('periode_awal')->first();

                        if ($rec) {
                            $action->fillForm([
                                'sj'           => (float) $rec->sj,
                                'sabtu'        => (float) $rec->sabtu,
                                'minggu'       => (float) $rec->minggu,
                                'hari_besar'   => (float) $rec->hari_besar,
                                'tidak_masuk'  => (float) $rec->tidak_masuk,
                                'sisa_jam'     => (float) $rec->sisa_jam,
                                'jumlah_hari'  => (float) $rec->jumlah_hari,
                            ]);
                        }
                    }
                })
                ->action(function (array $data) {
                    $rec = AbsensiRekap::where('karyawan_id', $data['karyawan_id'])
                        ->latest('periode_awal')->first();

                    if (!$rec) {
                        Notification::make()->title('Tidak ada rekap')
                            ->body('Karyawan belum memiliki rekap untuk diedit.')
                            ->warning()->send();
                        return;
                    }

                    $total = max(0, 
                        (float)($data['sj'] ?? 0)
                    + (float)($data['sabtu'] ?? 0)
                    + (float)($data['minggu'] ?? 0)
                    + (float)($data['hari_besar'] ?? 0)
                    - (float)($data['tidak_masuk'] ?? 0)
                    - (float)($data['sisa_jam'] ?? 0)
                    );

                    $rec->update([
                        'sj'           => $data['sj'] ?? 0,
                        'sabtu'        => $data['sabtu'] ?? 0,
                        'minggu'       => $data['minggu'] ?? 0,
                        'hari_besar'   => $data['hari_besar'] ?? 0,
                        'tidak_masuk'  => $data['tidak_masuk'] ?? 0,
                        'sisa_jam'     => $data['sisa_jam'] ?? 0,
                        'jumlah_hari'  => $data['jumlah_hari'] ?? $rec->jumlah_hari,
                        'total_jam'    => $total,
                    ]);

                    Notification::make()
                        ->title('Rekap diperbarui')
                        ->body('Rekap terbaru karyawan berhasil disimpan.')
                        ->success()->send();
                })
                ->closeModalByClickingAway(false),
        ];
    }

}