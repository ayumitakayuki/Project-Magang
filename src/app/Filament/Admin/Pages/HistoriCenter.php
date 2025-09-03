<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class HistoriCenter extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Histori Center';
    protected static ?string $title           = 'Histori Center';
    protected static ?string $navigationGroup = 'Penggajian';
    protected static string $view             = 'filament.pages.histori-center';
    protected static ?int $navigationSort     = 5;
}
