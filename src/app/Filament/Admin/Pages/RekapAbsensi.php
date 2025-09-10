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

class RekapAbsensi extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $title = 'Rekapitulasi Absensi';
    protected static string $view = 'filament.pages.rekap-absensi';

    public array $rekap = [];
    public $data_harian = [];

    public ?string $start_date = null;
    public ?string $end_date = null;

    public ?string $selected_id = null;
    public ?string $selected_name = null;
    public ?string $selected_lokasi = null;
    public ?string $selected_proyek = null;
    public ?string $status_karyawan = null;

    public $all_karyawan = null;
    public bool $show_all = false;
    public $lokasi_options = [];
    public $proyek_options = [];
    public float $totalSisaJam = 0;
    public float $jumlahHari = 0;
    public array $jumlahHariPerTanggal = [];
    public function mount(Request $request): void
    {
        $this->all_karyawan = Karyawan::get(['id_karyawan', 'nama']);
        $this->status_karyawan = $request->query('status_karyawan');

        $keyword = $request->query('karyawan_keyword');
        $this->selected_lokasi = $request->query('lokasi');
        $this->selected_proyek = $request->query('proyek');
        $this->show_all = $request->has('show_all') ? $request->query('show_all') === '1' : true;

        if ($keyword) {
            $matched = Karyawan::where('id_karyawan', $keyword)
                ->orWhere('nama', 'like', '%' . $keyword . '%')
                ->first();

            $this->selected_id = $matched?->id_karyawan;
            $this->selected_name = $matched?->nama;
            $this->status_karyawan = $matched?->status;
            $this->selected_lokasi = $matched?->lokasi;
            $this->selected_proyek = $matched?->jenis_proyek;

            if ($matched) {
                $this->show_all = false;
            }
        }

        $this->start_date = $request->query('start_date') ?? now()->subMonth()->toDateString();
        $this->end_date = $request->query('end_date') ?? now()->toDateString();

        $this->lokasi_options = Karyawan::query()
            ->distinct()
            ->pluck('lokasi')
            ->filter()
            ->values()
            ->all();

        $this->proyek_options = Karyawan::query()
            ->where('lokasi', 'proyek')
            ->whereNotNull('jenis_proyek')
            ->orderBy('jenis_proyek')
            ->distinct()
            ->pluck('jenis_proyek')
            ->unique()
            ->filter()
            ->values()
            ->all();

        $this->loadRekap();
    }

    public function loadRekap(bool $persist = false): void
    {
        // Auto-aktifkan show_all hanya jika SEMUA filter kosong
        if (
            !$this->selected_name &&
            (!$this->status_karyawan || $this->status_karyawan === 'all') &&
            !$this->selected_lokasi &&
            !$this->selected_proyek &&
            !$this->selected_id
        ) {
            $this->show_all = true;
        }

        /**
         * 1) PRIORITAS: filter berdasarkan NAMA (fix utama)
         */
        if ($this->selected_name) {
            // Rekap khusus 1 user
            $this->rekap = (array) app(AbsensiRekapService::class)->rekapUntukUser(
                $this->selected_name,
                $this->start_date,
                $this->end_date,
                $persist
            );

            $this->data_harian = Absensi::where('name', $this->selected_name)
                ->whereBetween('tanggal', [$this->start_date, $this->end_date])
                ->orderBy('tanggal')
                ->get();

            // Hitung jumlah hari & sisa jam per tanggal
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

            $this->totalSisaJam = $totalSisaJam;
            $this->jumlahHari = $totalHari;
            $this->jumlahHariPerTanggal = $jumlahHariPerTanggal;

            return; // ⬅️ penting: hentikan eksekusi di sini
        }

        /**
         * 2) FILTER BERDASARKAN LOKASI (workshop/proyek/other)
         */
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

            return; // ⬅️ hentikan eksekusi setelah cabang lokasi
        }

        /**
         * 3) SHOW ALL (default atau dipaksa oleh user)
         */
        if ($this->show_all) {
            $query = Absensi::whereBetween('tanggal', [$this->start_date, $this->end_date]);
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

        /**
         * 4) FALLBACK (jika tidak ada kondisi yang match)
         */
        $this->data_harian = [];
        $this->rekap = [];
    }

    public function filter(): void
    {
        $this->loadRekap(false); // hanya hitung & tampilkan
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
            // ini akan menyimpan ke DB karena kamu sudah set $persist = true di loadRekap(true)
            $this->loadRekap(true);

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