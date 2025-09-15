<?php

namespace App\Filament\Admin\Pages;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Services\AbsensiRekapService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Http\Request;
use Filament\Notifications\Notification;
use Livewire\Attributes\Url;

class RekapAbsensi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $title = 'Rekapitulasi Absensi';
    protected static string $view = 'filament.pages.rekap-absensi';

    public array $rekap = [];
    public $data_harian = [];

    #[Url] public ?string $start_date = null; 
    #[Url] public ?string $end_date = null;

    #[Url] public ?string $selected_id = null;
    #[Url] public ?string $selected_name = null;
    #[Url] public ?string $selected_lokasi = null;
    #[Url] public ?string $selected_proyek = null;
    #[Url] public ?string $status_karyawan = null;

    public $all_karyawan = null;
    public bool $show_all = false;
    public $lokasi_options = [];
    public $proyek_options = [];
    public float $totalSisaJam = 0;
    public float $jumlahHari = 0;
    public array $jumlahHariPerTanggal = [];
    public function mount(): void
    {
        $this->all_karyawan = Karyawan::get(['id_karyawan', 'nama']);

        // default tanggal kalau kosong (bukan dari request)
        $this->start_date ??= now()->subMonth()->toDateString();
        $this->end_date   ??= now()->toDateString();

        // isi nama/status/lokasi/proyek dari selected_id (jika ada)
        if ($this->selected_id && !$this->selected_name) {
            if ($m = Karyawan::where('id_karyawan', $this->selected_id)->first()) {
                $this->selected_name   = $m->nama;
                $this->status_karyawan ??= $m->status;
                $this->selected_lokasi ??= $m->lokasi;
                $this->selected_proyek ??= $m->jenis_proyek;
                $this->show_all = false;
            }
        }

        // opsi dropdown
        $this->lokasi_options = Karyawan::query()->distinct()->pluck('lokasi')->filter()->values()->all();

        $this->proyek_options = Karyawan::query()
            ->where('lokasi', 'proyek')
            ->whereNotNull('jenis_proyek')
            ->orderBy('jenis_proyek')
            ->distinct()->pluck('jenis_proyek')->unique()->filter()->values()->all();

        $this->loadRekap();
    }


    public function loadRekap(bool $persist = false): void
    {
        /* 0) Normalisasi state terlebih dulu */
        if ($this->selected_id && !$this->selected_name) {
            // Ambil nama RESMI dari DB berdasar ID agar tidak kena mismatch lowercase/dll.
            $this->selected_name = Karyawan::where('id_karyawan', $this->selected_id)->value('nama') ?: null;
        }
        // Jika user menghapus pilihan
        if (!$this->selected_id && $this->selected_name) {
            $id = Karyawan::where('nama', $this->selected_name)->value('id_karyawan');
            if (!$id) {
                $this->selected_name = null;
            }
        }

        // 0.1) Hitung show_all SEKALI saja: true hanya jika SEMUA filter kosong / default
        $this->show_all =
            empty($this->selected_id) &&
            empty($this->selected_name) &&
            (empty($this->status_karyawan) || $this->status_karyawan === 'all') &&
            empty($this->selected_lokasi) &&
            empty($this->selected_proyek);

        /* 1) PRIORITAS: filter berdasarkan NAMA (single user) */
        if ($this->selected_name) {
        // >>> pakai rekapSemuaUser agar struktur $rekap sama dengan "show all"
        $nama_karyawan = collect([$this->selected_name]);

        $this->rekap = app(AbsensiRekapService::class)->rekapSemuaUser(
            $this->start_date,
            $this->end_date,
            $nama_karyawan,
            $this->status_karyawan,   // boleh null / 'all'
            $this->selected_lokasi,   // boleh null
            $this->selected_proyek,   // boleh null
            $persist
        );

        // data harian untuk tabel kiri
        $this->data_harian = Absensi::where('name', $this->selected_name)
            ->whereBetween('tanggal', [$this->start_date, $this->end_date])
            ->orderBy('tanggal')
            ->get();

        // hitung jumlah hari & sisa jam per tanggal (punya kamu sendiri)
        $jumlahHariPerTanggal = app(AbsensiRekapService::class)
            ->hitungJumlahHariPerTanggal($this->data_harian);

        $totalSisaJam = 0;
        $totalHari = 0;
        foreach ($jumlahHariPerTanggal as $rekapPerTanggal) {
            if (isset($rekapPerTanggal['sisa_jam']) && is_numeric($rekapPerTanggal['sisa_jam'])) {
                $totalSisaJam += $rekapPerTanggal['sisa_jam'];
            }
            if (isset($rekapPerTanggal['jumlah_hari']) && is_numeric($rekapPerTanggal['jumlah_hari'])) {
                $totalHari += $rekapPerTanggal['jumlah_hari'];
            }
        }

        $this->totalSisaJam          = $totalSisaJam;
        $this->jumlahHari            = $totalHari;
        $this->jumlahHariPerTanggal  = $jumlahHariPerTanggal;

        // show_all hanya true kalau semua filter kosong
        $this->show_all = !(
            $this->selected_name ||
            $this->selected_id ||
            ($this->status_karyawan && $this->status_karyawan !== 'all') ||
            $this->selected_lokasi ||
            $this->selected_proyek
        );

        return;
    }

        /* 2) FILTER BERDASARKAN LOKASI */
        if ($this->selected_lokasi) {
            if ($this->selected_lokasi === 'workshop' || $this->selected_lokasi === 'proyek') {
                $nama_yang_pernah_absen = Absensi::whereBetween('tanggal', [$this->start_date, $this->end_date])
                    ->distinct()
                    ->pluck('name');

                $karyawanQuery = Karyawan::where('lokasi', $this->selected_lokasi);

                if ($this->selected_lokasi === 'proyek' && $this->selected_proyek) {
                    $karyawanQuery->where('jenis_proyek', $this->selected_proyek);
                }

                $nama_karyawan = $karyawanQuery
                    ->whereIn('nama', $nama_yang_pernah_absen)
                    ->pluck('nama');

                if ($nama_karyawan->isNotEmpty()) {
                    $query = Absensi::whereBetween('tanggal', [$this->start_date, $this->end_date])
                        ->whereIn('name', $nama_karyawan);

                    $this->data_harian = $query->orderBy('tanggal')->get();

                    $this->rekap = app(AbsensiRekapService::class)->rekapSemuaUser(
                        $this->start_date,
                        $this->end_date,
                        $nama_karyawan,
                        $this->status_karyawan,
                        $this->selected_lokasi,
                        $this->selected_proyek,
                        $persist
                    );
                } else {
                    $this->data_harian = [];
                    $this->rekap = [];
                }
            } else {
                // Lokasi selain workshop/proyek
                $karyawanQuery = Karyawan::where('lokasi', $this->selected_lokasi);
                $nama_karyawan = $karyawanQuery->pluck('nama');

                if ($nama_karyawan->isNotEmpty()) {
                    $query = Absensi::whereBetween('tanggal', [$this->start_date, $this->end_date])
                        ->whereIn('name', $nama_karyawan);

                    $this->data_harian = $query->orderBy('tanggal')->get();

                    $this->rekap = app(AbsensiRekapService::class)->rekapSemuaUser(
                        $this->start_date,
                        $this->end_date,
                        $nama_karyawan,
                        $this->status_karyawan,
                        $this->selected_lokasi,
                        $this->selected_proyek,
                        $persist
                    );
                } else {
                    $this->data_harian = [];
                    $this->rekap = [];
                }
            }

            return; // stop setelah cabang lokasi
        }

        /* 3) SHOW ALL */
        if ($this->show_all) {
            $query         = Absensi::whereBetween('tanggal', [$this->start_date, $this->end_date]);
            $karyawanQuery = Karyawan::query();

            if ($this->status_karyawan && $this->status_karyawan !== 'all') {
                $karyawanQuery->where('status', $this->status_karyawan);
            }
            if ($this->selected_lokasi) {
                $karyawanQuery->where('lokasi', $this->selected_lokasi);
            }
            if ($this->selected_lokasi === 'proyek' && $this->selected_proyek) {
                $karyawanQuery->where('jenis_proyek', $this->selected_proyek);
            }

            $nama_karyawan = $karyawanQuery->pluck('nama');

            if ($nama_karyawan->isNotEmpty()) {
                $query->whereIn('name', $nama_karyawan);
            }

            $this->data_harian = $query->orderBy('tanggal')->get();

            $this->rekap = app(AbsensiRekapService::class)->rekapSemuaUser(
                $this->start_date,
                $this->end_date,
                $nama_karyawan,
                $this->status_karyawan,
                $this->selected_lokasi,
                $this->selected_proyek,
                $persist
            );

            return;
        }

        /* 4) FALLBACK (tak ada kondisi yang match) */
        $this->data_harian = [];
        $this->rekap = [];
    }

    public function filter(): void
    {
        // Derive nama dari ID → hindari mismatch kapitalisasi / label UI
        if ($this->selected_id) {
            $nama = Karyawan::where('id_karyawan', $this->selected_id)->value('nama');
            $this->selected_name = $nama ?: null;
            $this->show_all = false;
        } else {
            $this->selected_name = null;
        }

        $this->loadRekap(false);
    }

    public function simpan(): void
    {
        if (!$this->start_date || !$this->end_date) {
            Notification::make()
                ->title('Pilih periode dulu')
                ->body('Isi Periode Awal & Akhir sebelum menyimpan.')
                ->warning()->send();
            return;
        }

        try {
            // Simpan rekapmu seperti biasa
            $this->loadRekap(true);

            // --- Kirim event ke browser: pakai PK & KODE sekaligus ---
            $karyawanPk = Karyawan::where('id', $this->selected_id)->value('id')
                ?? Karyawan::where('id_karyawan', $this->selected_id)->value('id');

            $this->dispatch(
                'rekap-saved',
                karyawanId:   (string) $karyawanPk,        // PK karyawans.id (jika ada)
                karyawanKode: (string) $this->selected_id, // id_karyawan (kode/NIP)
                start:        (string) $this->start_date,
                end:          (string) $this->end_date,
            );

            Notification::make()
                ->title('Rekap tersimpan')
                ->body(
                    'Periode: ' .
                    \Carbon\Carbon::parse($this->start_date)->format('d M Y') . ' – ' .
                    \Carbon\Carbon::parse($this->end_date)->format('d M Y')
                )
                ->success()->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal menyimpan')
                ->body($e->getMessage())
                ->danger()->send();
            report($e);
        }
    }


}