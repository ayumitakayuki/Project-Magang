<?php

namespace App\Filament\Admin\Pages;

use App\Models\Absensi;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Session;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class AbsensiPreview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'Preview Import Absensi';
    protected static string $view = 'filament.pages.absensi-preview';
    protected static ?string $slug = 'absensi-preview';

    public array $data = [];

    public function mount(): void
    {
        $this->data = Session::get('preview_absensi', []);

        foreach ($this->data as &$row) {
            $row['tanggal'] = !empty($row['tanggal']) ? Carbon::parse($row['tanggal'])->format('Y-m-d') : null;

            $row['masuk_pagi']    = $this->formatTime($row['masuk_pagi'] ?? null);
            $row['keluar_siang']  = $this->formatTime($row['keluar_siang'] ?? null);
            $row['masuk_siang']   = $this->formatTime($row['masuk_siang'] ?? null);
            $row['pulang_kerja']  = $this->formatTime($row['pulang_kerja'] ?? null);
            $row['masuk_lembur']  = $this->formatTime($row['masuk_lembur'] ?? null);
            $row['pulang_lembur'] = $this->formatTime($row['pulang_lembur'] ?? null);
        }
    }

    private function formatTime($time): ?string
    {
        try {
            return !empty($time) ? Carbon::parse($time)->format('H:i:s') : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function saveAllToDatabase(): void
    {
        $insertData = [];
        foreach ($this->data as $row) {
            $insertData[] = [
                'name' => $row['name'] ?? '',
                'tanggal' => $row['tanggal'] ?? null,
                'masuk_pagi' => $row['masuk_pagi'] ?? null,
                'keluar_siang' => $row['keluar_siang'] ?? null,
                'masuk_siang' => $row['masuk_siang'] ?? null,
                'pulang_kerja' => $row['pulang_kerja'] ?? null,
                'masuk_lembur' => $row['masuk_lembur'] ?? null,
                'pulang_lembur' => $row['pulang_lembur'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Absensi::insert($insertData);

        session()->forget('preview_absensi');

        Notification::make()
            ->title('Berhasil')
            ->body('Data berhasil disimpan ke database.')
            ->success()
            ->send();

        redirect()->route('filament.admin.resources.absensis.index');
    }

    public function clearData(): void
    {
        session()->forget('preview_absensi');

        Notification::make()
            ->title('Data Dikosongkan')
            ->body('Data yang diimpor telah dihapus.')
            ->danger()
            ->send();

        $this->data = [];

        redirect()->route('filament.admin.pages.absensi-preview');
    }
}
