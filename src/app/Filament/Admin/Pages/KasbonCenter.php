<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Karyawan;

class KasbonCenter extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Kasbon';
    protected static ?string $title           = 'Kasbon';
    protected static ?string $navigationGroup = 'Penggajian'; // sesuaikan
    protected static string $view             = 'filament.pages.kasbon-center';
    protected static ?int $navigationSort     = 4;
}
