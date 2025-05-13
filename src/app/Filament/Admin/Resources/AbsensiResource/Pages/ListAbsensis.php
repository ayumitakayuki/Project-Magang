<?php

namespace App\Filament\Admin\Resources\AbsensiResource\Pages;

use App\Filament\Admin\Resources\AbsensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;


class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getFooter(): ?\Illuminate\View\View
    {
        return view('filament.pages.rekap-button');
    }
}
