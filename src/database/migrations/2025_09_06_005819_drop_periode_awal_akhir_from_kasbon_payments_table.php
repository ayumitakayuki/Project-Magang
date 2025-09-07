<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KasbonPaymentResource\Pages;
use App\Models\KasbonPayment;
use App\Models\KasbonLoan;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KasbonPaymentResource extends Resource
{
    protected static ?string $model = KasbonPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // LOAN
            Forms\Components\Select::make('kasbon_loan_id')
                ->label('Loan')
                ->relationship('loan', 'id')
                ->searchable()
                ->preload()
                ->required()
                ->getOptionLabelFromRecordUsing(function (KasbonLoan $loan) {
                    $nama = optional($loan->karyawan)->nama ?? '—';
                    return "{$loan->id} • {$nama} • Sisa: Rp " . number_format($loan->sisa_saldo, 0, ',', '.');
                })
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state && ($loan = KasbonLoan::find($state))) {
                        $set('nominal', (string) min((float) $loan->cicilan, (float) $loan->sisa_saldo));
                    }
                }),

            // TANGGAL PEMBAYARAN
            Forms\Components\DatePicker::make('tanggal')
                ->label('Tanggal')
                ->required()
                ->default(now()),

            // ===== PERIODE (INPUT SEMENTARA, TIDAK DISIMPAN) =====
            Forms\Components\DatePicker::make('periode_awal_tmp')
                ->label('Periode Awal')
                ->helperText('Isi sesuai penutupan buku (dinamis).')
                ->reactive()
                ->dehydrated(false) // <— tidak disimpan ke DB
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $awal = $get('periode_awal_tmp');
                    $akhir = $get('periode_akhir_tmp');
                    if ($awal && $akhir) {
                        $set('periode_label',
                            Carbon::parse($awal)->format('d M Y') . ' – ' . Carbon::parse($akhir)->format('d M Y')
                        );
                    }
                }),

            Forms\Components\DatePicker::make('periode_akhir_tmp')
                ->label('Periode Akhir')
                ->reactive()
                ->dehydrated(false) // <— tidak disimpan ke DB
                ->minDate(fn (Get $get) => $get('periode_awal_tmp'))
                ->rule(function (Get $get) {
                    return function (string $attribute, $value, Closure $fail) use ($get) {
                        $awal = $get('periode_awal_tmp');
                        if ($awal && $value && Carbon::parse($value)->lt(Carbon::parse($awal))) {
                            $fail('Periode akhir harus setelah atau sama dengan periode awal.');
                        }
                    };
                })
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $awal = $get('periode_awal_tmp');
                    $akhir = $get('periode_akhir_tmp');
                    if ($awal && $akhir) {
                        $set('periode_label',
                            Carbon::parse($awal)->format('d M Y') . ' – ' . Carbon::parse($akhir)->format('d M Y')
                        );
                    }
                }),

            // ===== LABEL YANG DISIMPAN KE DB =====
            Forms\Components\TextInput::make('periode_label')
                ->label('Periode (Label)')
                ->required()
                ->helperText('Akan otomatis terisi dari Periode Awal/Akhir, tapi masih bisa diedit.'),

            // NOMINAL
            Forms\Components\TextInput::make('nominal')
                ->label('Nominal Pembayaran')
                ->numeric()
                ->required()
                ->prefix('Rp')
                ->rule(function (Get $get) {
                    return function (string $attribute, $value, Closure $fail) use ($get) {
                        $loanId = $get('kasbon_loan_id');
                        if ($loanId && ($loan = KasbonLoan::find($loanId))) {
                            if ((float) $value > (float) $loan->sisa_saldo) {
                                $fail('Nominal melebihi sisa saldo kasbon (Rp ' . number_format($loan->sisa_saldo, 0, ',', '.') . ').');
                            }
                        }
                    };
                }),

            // SUMBER & CATATAN
            Forms\Components\Select::make('sumber')
                ->label('Sumber')
                ->options(['slip' => 'Slip Gaji', 'manual' => 'Manual'])
                ->default('slip')
                ->required(),

            Forms\Components\TextInput::make('catatan')
                ->label('Catatan')
                ->maxLength(255),
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
                    ->label('Tanggal')->date('d M Y')->sortable(),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')->money('IDR', true)->sortable(),

                Tables\Columns\BadgeColumn::make('sumber')->label('Sumber'),

                Tables\Columns\TextColumn::make('periode_label')
                    ->label('Periode'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKasbonPayments::route('/'),
            'create' => Pages\CreateKasbonPayment::route('/create'),
            'edit'   => Pages\EditKasbonPayment::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
