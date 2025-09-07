<?php

namespace App\Filament\Admin\Resources\KasbonPaymentResource\Pages;

use App\Filament\Admin\Resources\KasbonPaymentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListKasbonPayments extends ListRecords
{
    protected static string $resource = KasbonPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create'), // opsional: ubah label/icon
        ];
    }
}
