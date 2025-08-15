<?php

namespace App\Filament\Admin\Resources\KasbonPaymentResource\Pages;

use App\Filament\Admin\Resources\KasbonPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKasbonPayment extends EditRecord
{
    protected static string $resource = KasbonPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
