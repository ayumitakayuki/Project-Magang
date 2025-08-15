<?php

namespace App\Filament\Admin\Resources\KasbonLoanResource\Pages;

use App\Filament\Admin\Resources\KasbonLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKasbonLoan extends EditRecord
{
    protected static string $resource = KasbonLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
