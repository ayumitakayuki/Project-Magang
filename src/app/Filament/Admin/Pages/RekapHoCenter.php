<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class RekapHoCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $title = 'Rekap HO';
    protected static string $view = 'filament.pages.rekap-ho-center';
    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }
}
