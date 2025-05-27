<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KaryawanResource\Pages;
use App\Models\Karyawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Data Karyawan';
    protected static ?string $pluralLabel = 'Karyawan';
    protected static ?string $navigationGroup = 'Manajemen Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('id_karyawan')
                ->label('ID Karyawan')
                ->required()
                ->maxLength(20),

            TextInput::make('nama')
                ->label('Nama')
                ->required()
                ->maxLength(100),

            Select::make('status')
                ->label('Status')
                ->options([
                    'staff' => 'Staff',
                    'harian tetap' => 'Harian Tetap',
                    'harian lepas' => 'Harian Lepas',
                ])
                ->required(),

            Select::make('lokasi')
                ->label('Lokasi')
                ->options([
                    'workshop' => 'Workshop',
                    'proyek' => 'Proyek',
                ])
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set) => $state !== 'proyek' ? $set('jenis_proyek', null) : null),

            Select::make('jenis_proyek')
                ->label('Jenis Proyek')
                ->options([
                    'Proyek A' => 'Proyek A',
                    'Proyek B' => 'Proyek B',
                    'Proyek C' => 'Proyek C',
                ])
                ->visible(fn ($get) => $get('lokasi') === 'proyek')
                ->required(fn ($get) => $get('lokasi') === 'proyek'),

            TextInput::make('gaji_perbulan')
                ->label('Gaji Per Bulan')
                ->numeric(),

            TextInput::make('gaji_lembur_reguler')
                ->label('Gaji Lembur Reguler')
                ->numeric(),

            TextInput::make('gaji_lembur_sabtu')
                ->label('Gaji Lembur Sabtu')
                ->numeric(),

            TextInput::make('gaji_lembur_minggu_haribesar')
                ->label('Gaji Lembur Minggu/HariBesar')
                ->numeric(),

            TextInput::make('gaji_harian')
                ->label('Gaji Harian')
                ->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_karyawan')
                    ->label('ID Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),

                TextColumn::make('lokasi')
                    ->label('Lokasi')
                    ->sortable(),

                TextColumn::make('jenis_proyek')
                    ->label('Jenis Proyek')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $validProjects = ['Proyek A', 'Proyek B', 'Proyek C'];
                        return $record->lokasi === 'proyek' && in_array($state, $validProjects) ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('gaji_perbulan')
                    ->label('Gaji Per Bulan')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('gaji_lembur_reguler')
                    ->label('Gaji Lembur Reguler')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('gaji_lembur_sabtu')
                    ->label('Gaji Lembur Sabtu')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('gaji_lembur_minggu_haribesar')
                    ->label('Gaji Lembur Minggu/HariBesar')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('gaji_harian')
                    ->label('Gaji Harian')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
            ])
            ->filters([
                SelectFilter::make('lokasi')
                    ->label('Filter Lokasi')
                    ->options(fn () => Karyawan::query()->distinct()->pluck('lokasi', 'lokasi')->filter()),

                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options(fn () => Karyawan::query()->distinct()->pluck('status', 'status')->filter()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKaryawans::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }
}
