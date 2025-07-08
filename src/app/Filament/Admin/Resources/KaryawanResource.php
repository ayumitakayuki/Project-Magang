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

            TextInput::make('gaji_lembur')
                ->label('Gaji Lembur')
                ->numeric(),
                
            TextInput::make('gaji_harian')
                ->label('Gaji Harian')
                ->numeric(),

            TextInput::make('uang_makan_lembur_malam')
                ->label('Uang Makan Lembur Malam')
                ->numeric(),

            TextInput::make('uang_makan_lembur_jalan')
                ->label('Uang Makan Lembur Jalan')
                ->numeric(),

            TextInput::make('potongan_bpjs_kesehatan')
                ->label('Potongan BPJS Kesehatan')
                ->numeric(),

            TextInput::make('potongan_tenaga_kerja')
                ->label('Potongan Tenaga Kerja')
                ->numeric(),

            TextInput::make('potongan_bpjs_kesehatan_tk')
                ->label('Potongan BPJS Kesehatan + TK')
                ->numeric(),
                
            TextInput::make('faktor_sj')
                ->label('Faktor Senin s/d Jumat')
                ->numeric()
                ->step('0.1'),

            TextInput::make('faktor_sabtu')
                ->label('Faktor Sabtu')
                ->numeric()
                ->step('0.1'),

            TextInput::make('faktor_minggu')
                ->label('Faktor Minggu')
                ->numeric()
                ->step('0.1'),

            TextInput::make('faktor_hari_besar')
                ->label('Faktor Hari Besar')
                ->numeric()
                ->step('0.1'),       
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
                    return $record->lokasi === 'proyek' && in_array($state, $validProjects) ? $state : '-';
                }),

                TextColumn::make('gaji_perbulan')
                    ->label('Gaji Per Bulan')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('gaji_lembur')
                    ->label('Gaji Lemburs')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('gaji_harian')
                    ->label('Gaji Harian')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
                
                TextColumn::make('uang_makan_lembur_malam')
                    ->label('Uang Makan Lembur Malam')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('uang_makan_lembur_jalan')
                    ->label('Uang Makan Lembur Jalan')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('potongan_bpjs_kesehatan')
                    ->label('Potongan BPJS Kesehatan')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('potongan_tenaga_kerja')
                    ->label('Potongan Tenaga Kerja')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                TextColumn::make('potongan_bpjs_kesehatan_tk')
                    ->label('Potongan BPJS Kesehatan + TK')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
                TextColumn::make('faktor_sj')->label('Faktor SJ'),
                TextColumn::make('faktor_sabtu')->label('Faktor Sabtu'),
                TextColumn::make('faktor_minggu')->label('Faktor Minggu'),
                TextColumn::make('faktor_hari_besar')->label('Faktor Hari Besar'),
            ])
            ->filters([
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
