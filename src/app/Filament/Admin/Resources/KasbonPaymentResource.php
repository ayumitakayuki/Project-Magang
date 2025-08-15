<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KasbonPaymentResource\Pages;
use App\Models\KasbonPayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\KasbonLoan;

class KasbonPaymentResource extends Resource
{
    protected static ?string $model = KasbonPayment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('kasbon_loan_id')
                ->label('Loan')
                ->relationship('loan', 'id') // relasi di model KasbonPayment -> belongsTo KasbonLoan::class
                ->searchable()->preload()->required()
                ->getOptionLabelFromRecordUsing(function (KasbonLoan $loan) {
                    $nama = optional($loan->karyawan)->nama ?? '—';
                    return "{$loan->id} • {$nama} • Sisa: Rp " . number_format($loan->sisa_saldo, 0, ',', '.');
                })
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state && ($loan = KasbonLoan::find($state))) {
                        // default: isi nominal pake cicilan atau sisa_saldo (mana yg lebih kecil)
                        $set('nominal', (string) min((float)$loan->cicilan, (float)$loan->sisa_saldo));
                        // default periode label
                        $set('periode_label', now()->format('01–15 M Y'));
                    }
                }),

            Forms\Components\DatePicker::make('tanggal')
                ->required()
                ->default(now()),

            Forms\Components\TextInput::make('nominal')
                ->label('Nominal Pembayaran')
                ->numeric()->required()->prefix('Rp')
                ->rule(function (callable $get) {
                    // validasi runtime: nominal ≤ sisa_saldo loan
                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                        $loanId = $get('kasbon_loan_id');
                        if ($loanId && ($loan = KasbonLoan::find($loanId))) {
                            if ((float)$value > (float)$loan->sisa_saldo) {
                                $fail('Nominal melebihi sisa saldo kasbon (Rp '.number_format($loan->sisa_saldo,0,',','.').').');
                            }
                        }
                    };
                }),

            Forms\Components\Select::make('sumber')
                ->options(['slip' => 'Slip Gaji', 'manual' => 'Manual'])
                ->default('manual')->required(),

            Forms\Components\TextInput::make('periode_label')
                ->label('Periode')->maxLength(50),

            Forms\Components\TextInput::make('catatan')
                ->label('Catatan')->maxLength(255),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.karyawan.nama')
                    ->label('Karyawan')->searchable(),

                Tables\Columns\TextColumn::make('kasbon_loan_id')
                    ->label('Loan ID')->sortable(),

                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->money('IDR', true)->sortable(),

                Tables\Columns\BadgeColumn::make('sumber'),

                Tables\Columns\TextColumn::make('periode_label')->label('Periode'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKasbonPayments::route('/'),
            'create' => Pages\CreateKasbonPayment::route('/create'),
            'edit' => Pages\EditKasbonPayment::route('/{record}/edit'),
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
