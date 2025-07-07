<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Gaji;

class DetailSlipGaji extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static string $view = 'filament.pages.detail-slip-gaji';

    public ?Gaji $gaji = null;

    public function mount(): void
    {
        $id = request('id');
        $this->gaji = Gaji::with('details')->findOrFail($id);
    }

    public static function canAccess(): bool
    {
        return request()->has('id');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // jangan tampilkan di sidebar
    }
}

