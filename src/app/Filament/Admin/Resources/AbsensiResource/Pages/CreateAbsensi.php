<?php

namespace App\Filament\Admin\Resources\AbsensiResource\Pages;

use App\Filament\Admin\Resources\AbsensiResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AbsensiImport;
use Illuminate\Validation\ValidationException;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;

    public array $previewData = [];

    protected $casts = [
        'previewData' => 'array',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->url(route('absensi.download-template'))
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->openUrlInNewTab(),

            Action::make('importExcel')
                ->label('Import Excel')
                ->form([
                    FileUpload::make('file')
                        ->directory('imports')
                        ->label('Pilih file Excel')
                        ->required()
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->maxSize(10240),
                ])
                ->action(function (array $data) {
                    $filePath = $data['file'] ?? null;
                    $originalName = basename($filePath);

                    if ($originalName !== 'template-absensi.xlsx') {
                        Notification::make()
                            ->title('Format Salah')
                            ->body('Silakan gunakan file template-absensi.xlsx untuk mengimpor data.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $path = storage_path('app/public/' . $filePath);

                    try {
                        $import = new AbsensiImport;
                        Excel::import($import, $path);

                        session()->put('preview_absensi', $import->getPreviewData());

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Data berhasil diimpor.')
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.pages.absensi-preview');
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Gagal Import')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->icon('heroicon-m-arrow-up-tray')
                ->color('warning'),
        ];
    }

    protected function getFormSchema(): array
    {
        $schema = [];

        if (!empty($this->previewData)) {
            $schema[] = Section::make('Preview Data Import')
                ->schema([
                    Placeholder::make('PreviewTable')
                        ->content(function () {
                            $html = '<table class="w-full table-auto border border-gray-300">';
                            $html .= '<thead><tr class="bg-gray-100">';
                            $html .= '<th class="border p-2">Nama</th>';
                            $html .= '<th class="border p-2">Tanggal</th>';
                            $html .= '<th class="border p-2">Masuk Pagi</th>';
                            $html .= '<th class="border p-2">Keluar Siang</th>';
                            $html .= '<th class="border p-2">Masuk Siang</th>';
                            $html .= '<th class="border p-2">Pulang Kerja</th>';
                            $html .= '<th class="border p-2">Masuk Lembur</th>';
                            $html .= '<th class="border p-2">Pulang Lembur</th>';
                            $html .= '</tr></thead><tbody>';

                            foreach ($this->previewData as $row) {
                                $html .= '<tr>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['name']) . '</td>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['tanggal']) . '</td>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['masuk_pagi']) . '</td>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['keluar_siang']) . '</td>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['masuk_siang']) . '</td>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['pulang_kerja']) . '</td>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['masuk_lembur']) . '</td>';
                                $html .= '<td class="border p-2">' . htmlspecialchars($row['pulang_lembur']) . '</td>';
                                $html .= '</tr>';
                            }

                            $html .= '</tbody></table>';
                            return $html;
                        })
                        ->disableLabel(),
                ]);
        }

        return $schema;
    }
}
