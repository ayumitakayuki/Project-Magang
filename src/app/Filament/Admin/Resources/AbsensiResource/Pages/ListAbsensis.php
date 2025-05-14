<?php

namespace App\Filament\Admin\Resources\AbsensiResource\Pages;

use App\Filament\Admin\Resources\AbsensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('lihatRekap')
                ->label('Lihat Rekapitulasi Absensi')
                ->icon('heroicon-o-chart-bar')
                ->url(route('filament.admin.pages.rekap-absensi')) // sesuaikan jika route kamu beda
                ->color('success'),

            Actions\CreateAction::make(), // tombol New Absensi tetap tampil
        ];
    }
}
