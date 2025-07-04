<?php

namespace App\Filament\Admin\Pages;

use App\Models\Absensi;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Session;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class AbsensiPreview extends Page
{
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = null;
    protected static string $view = 'filament.pages.absensi-preview';
    protected static ?string $slug = 'absensi-preview';

    public array $data = [];

    public function mount(): void
    {
        $this->data = session('preview_absensi', []);
    }

    public function saveAllToDatabase(): void
    {
        $insertData = [];

        foreach ($this->data as $row) {
            try {
                $tanggal = \Carbon\Carbon::parse($row['tanggal'])->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }

            $exists = Absensi::where('name', $row['name'] ?? '')
                ->whereDate('tanggal', $tanggal)
                ->exists();

            if (! $exists) {
                $insertData[] = [
                    'name' => $row['name'] ?? '',
                    'tanggal' => $tanggal,
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
        }

        if (!empty($insertData)) {
            Absensi::insert($insertData);
        }

        session()->forget('preview_absensi');
        $this->data = [];

        Notification::make()
            ->title('Berhasil')
            ->body('Data berhasil disimpan tanpa duplikasi.')
            ->success()
            ->send();

        redirect()->route('filament.admin.resources.absensis.index');
    }


    public function clearData(): void
    {
        session()->forget('preview_absensi');
        $this->data = [];

        Notification::make()
            ->title('Data Dihapus')
            ->body('Data hasil import telah dikosongkan.')
            ->danger()
            ->send();

        redirect()->route('filament.admin.pages.absensi-preview');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
