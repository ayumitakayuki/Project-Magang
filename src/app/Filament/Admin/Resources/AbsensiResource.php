<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AbsensiResource\Pages;
use App\Models\Absensi;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Arr;
use App\Models\Karyawan;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required(),

                Forms\Components\TimePicker::make('masuk_pagi')
                    ->label('Masuk Pagi')
                    ->seconds(true),

                Forms\Components\TimePicker::make('keluar_siang')
                    ->label('Keluar Siang')
                    ->seconds(true),

                Forms\Components\TimePicker::make('masuk_siang')
                    ->label('Masuk Siang')
                    ->seconds(true),

                Forms\Components\TimePicker::make('pulang_kerja')
                    ->label('Pulang Kerja')
                    ->seconds(true),

                Forms\Components\TimePicker::make('masuk_lembur')
                    ->label('Masuk Lembur')
                    ->seconds(true),

                Forms\Components\TimePicker::make('pulang_lembur')
                    ->label('Pulang Lembur')
                    ->seconds(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                TextColumn::make('masuk_pagi')->label('Masuk Pagi'),
                TextColumn::make('keluar_siang')->label('Keluar Siang'),
                TextColumn::make('masuk_siang')->label('Masuk Siang'),
                TextColumn::make('pulang_kerja')->label('Pulang Kerja'),
                TextColumn::make('masuk_lembur')->label('Masuk Lembur'),
                TextColumn::make('pulang_lembur')->label('Pulang Lembur'),
            ])
            ->filters([
    // Periode tanggal (manual)
        Filter::make('periode_tanggal')
            ->form([
                Forms\Components\DatePicker::make('from')->label('Dari'),
                Forms\Components\DatePicker::make('until')->label('Sampai'),
            ])
            ->query(function ($query, array $data) {
                return $query
                    ->when($data['from']  ?? null, fn ($q, $date) => $q->whereDate('tanggal', '>=', $date))
                    ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('tanggal', '<=', $date));
            }),

            // Status karyawan (opsi ditarik dari DB karyawan)
            SelectFilter::make('status')
                ->label('Status Karyawan')
                ->options(fn () => Karyawan::query()
                    ->whereNotNull('status')
                    ->distinct()
                    ->orderBy('status')
                    ->pluck('status', 'status')
                    ->toArray()
                )
                ->relationship('karyawan', 'status'),

            // Lokasi (opsi dari DB karyawan)
            SelectFilter::make('lokasi')
                ->label('Lokasi')
                ->options(fn () => Karyawan::query()
                    ->whereNotNull('lokasi')
                    ->distinct()
                    ->orderBy('lokasi')
                    ->pluck('lokasi', 'lokasi')
                    ->toArray()
                )
                ->relationship('karyawan', 'lokasi'),

            // Jenis Proyek (opsi dari DB karyawan.jenis_proyek), hanya hit untuk lokasi 'proyek'
            SelectFilter::make('jenis_proyek')
                ->label('Jenis Proyek')
                ->options(fn () => Karyawan::query()
                    ->where('lokasi', 'proyek')
                    ->whereNotNull('jenis_proyek')
                    ->distinct()
                    ->orderBy('jenis_proyek')
                    ->pluck('jenis_proyek', 'jenis_proyek')
                    ->toArray()
                )
                ->multiple()
                ->preload()
                ->query(function ($query, $state) {
                    // dukung struktur ['values'=>[...]] atau array langsung
                    $values = Arr::wrap(data_get($state, 'values', $state));
                    $values = array_values(array_filter(Arr::flatten($values), fn ($v) => filled($v)));
                    if (empty($values)) return $query;

                    return $query->whereHas('karyawan', function ($q) use ($values) {
                        $q->where('lokasi', 'proyek')
                        ->whereIn('jenis_proyek', $values);
                    });
                }),
        ])
        ->paginationPageOptions([5, 10, 25, 50, 100, 'all'])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
}
