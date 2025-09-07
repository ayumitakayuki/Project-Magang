<?php

namespace App\Filament\Admin\Resources\KasbonPaymentResource\Pages;

use App\Filament\Admin\Resources\KasbonPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKasbonPayment extends CreateRecord
{
    protected static string $resource = KasbonPaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $awal  = $this->data['periode_awal_tmp']  ?? null;
        $akhir = $this->data['periode_akhir_tmp'] ?? null;

        if ($awal && $akhir) {
            $data['periode_label'] =
                \Carbon\Carbon::parse($awal)->format('d M Y') . ' – ' .
                \Carbon\Carbon::parse($akhir)->format('d M Y');
        }
        return $data;
    }

}
