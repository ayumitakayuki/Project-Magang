<?php

namespace App\Filament\Admin\Resources\KaryawanResource\Pages;

use App\Filament\Admin\Resources\KaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KaryawanImport;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class ListKaryawans extends ListRecords
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ✅ Tambahkan tombol Create
            Actions\CreateAction::make(),

            // ✅ Tombol download template (tetap)
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(asset('templates/template-karyawan.xlsx'))
                ->openUrlInNewTab(),

            // ✅ Tombol upload Excel (tetap)
            Action::make('importExcel')
                ->label('Upload Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required()
                        ->directory('uploads')
                        ->preserveFilenames(),
                ])
                ->action(function (array $data) {
                    $filename = basename($data['file']); // Ambil nama asli file

                    $path = storage_path('app/public/' . $data['file']);

                    try {
                        Excel::import(new KaryawanImport($filename), $path);

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Data karyawan berhasil diimpor.')
                            ->success()
                            ->send();
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Gagal Import')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
