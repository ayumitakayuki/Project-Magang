<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KasbonLoanResource\Pages;
use App\Filament\Admin\Resources\KasbonLoanResource\RelationManagers\PaymentsRelationManager;
use App\Models\KasbonLoan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KasbonLoanResource extends Resource
{
    protected static ?string $model = KasbonLoan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Data Kasbon')->schema([

                Forms\Components\Select::make('karyawan_id')
                    ->relationship('karyawan', 'nama') // sesuaikan field nama
                    ->searchable()->preload()->required()
                    ->label('Karyawan'),

                Forms\Components\DatePicker::make('tanggal')
                    ->default(now())->required(),

                Forms\Components\TextInput::make('pokok')
                    ->label('Pokok Kasbon')
                    ->numeric()->required()
                    ->prefix('Rp')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $tenor = (int) ($get('tenor') ?: 0);
                        if ($tenor > 0 && $state !== null) {
                            $set('cicilan', (string) ceil(((float)$state) / $tenor));
                            $set('sisa_saldo', $state);
                        }
                    }),

                Forms\Components\TextInput::make('tenor')
                    ->label('Tenor (X kali)')
                    ->numeric()->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $pokok = (float) ($get('pokok') ?: 0);
                        $tenor = max(1, (int) $state);
                        if ($pokok > 0) {
                            $set('cicilan', (string) ceil($pokok / $tenor));
                        }
                        $set('sisa_kali', $tenor);
                    }),

                Forms\Components\TextInput::make('cicilan')
                    ->label('Cicilan per Periode')
                    ->numeric()->required()
                    ->prefix('Rp'),

                Forms\Components\TextInput::make('sisa_kali')
                    ->label('Sisa Kali')
                    ->numeric()->default(0)->required(),

                Forms\Components\TextInput::make('sisa_saldo')
                    ->label('Sisa Saldo')
                    ->numeric()->default(0)->required()
                    ->prefix('Rp'),

                Forms\Components\Select::make('status')
                    ->options(['aktif' => 'Aktif', 'lunas' => 'Lunas', 'ditutup' => 'Ditutup'])
                    ->default('aktif')->required(),

                Forms\Components\TextInput::make('keterangan')
                    ->label('Keterangan')->maxLength(255),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query
                    ->withCount('payments')
                    ->withSum('payments as payments_sum_nominal', 'nominal');
            })
            ->columns([
                Tables\Columns\TextColumn::make('karyawan.nama')->label('Karyawan')->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('pokok')->money('IDR', true)->label('Pokok'),
                Tables\Columns\TextColumn::make('tenor')->label('X'),
                Tables\Columns\TextColumn::make('cicilan')->money('IDR', true),

                // ⬇️ pakai accessor realtime (berdasarkan payments)
                Tables\Columns\TextColumn::make('sisa_kali_realtime')
                    ->label('Sisa X')
                    ->state(fn ($record) => $record->sisa_kali_realtime)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('sisa_saldo_realtime')
                    ->label('Sisa Saldo')
                    ->state(fn ($record) => $record->sisa_saldo_realtime)
                    ->money('IDR', true)
                    ->sortable(false),

                Tables\Columns\BadgeColumn::make('status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKasbonLoans::route('/'),
            'create' => Pages\CreateKasbonLoan::route('/create'),
            'edit' => Pages\EditKasbonLoan::route('/{record}/edit'),
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

}
