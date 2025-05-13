<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KaryawanResource\Pages;
use App\Models\Karyawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
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

            TextInput::make('posisi')
                ->label('Posisi')
                ->maxLength(100),

            TextInput::make('bagian')
                ->label('Bagian')
                ->maxLength(100),

            TextInput::make('gaji_perbulan')
                ->label('Gaji Per Bulan')
                ->numeric(),

            TextInput::make('gaji_lembur')
                ->label('Gaji Lembur')
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
                TextColumn::make('id_karyawan')->label('ID Karyawan')->searchable()->sortable(),
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('posisi')->label('Posisi')->sortable(),
                TextColumn::make('bagian')->label('Bagian')->sortable(),
                TextColumn::make('gaji_perbulan')
                    ->label('Gaji Per Bulan')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
                TextColumn::make('gaji_lembur')
                    ->label('Gaji Lembur')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
                TextColumn::make('gaji_harian')
                    ->label('Gaji Harian')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),
            ])
            ->filters([
                SelectFilter::make('bagian')
                    ->label('Filter Bagian')
                    ->options(fn () => Karyawan::query()->distinct()->pluck('bagian', 'bagian')->filter()),

                SelectFilter::make('posisi')
                    ->label('Filter Posisi')
                    ->options(fn () => Karyawan::query()->distinct()->pluck('posisi', 'posisi')->filter()),
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
