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
                ->seconds(false),

            Forms\Components\TimePicker::make('keluar_siang')
                ->label('Keluar Siang')
                ->seconds(false),

            Forms\Components\TimePicker::make('masuk_siang')
                ->label('Masuk Siang')
                ->seconds(false),

            Forms\Components\TimePicker::make('pulang_kerja')
                ->label('Pulang Kerja')
                ->seconds(false),

            Forms\Components\TimePicker::make('masuk_lembur')
                ->label('Masuk Lembur')
                ->seconds(false),

            Forms\Components\TimePicker::make('pulang_lembur')
                ->label('Pulang Lembur')
                ->seconds(false),
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
                SelectFilter::make('status')
                    ->label('Status Karyawan')
                    ->options([
                        'harian tetap' => 'Harian Tetap',
                        'harian lepas' => 'Harian Lepas',
                    ])
                    ->relationship('karyawan', 'status'),

                SelectFilter::make('lokasi')
                    ->label('Lokasi')
                    ->options([
                        'workshop' => 'Workshop',
                        'proyek' => 'Proyek',
                    ])
                    ->relationship('karyawan', 'lokasi'),
            ])
            ->paginationPageOptions([5, 10, 25, 50, 100, 'all']) // Tambahkan ini
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
