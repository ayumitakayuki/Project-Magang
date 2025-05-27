<?php

namespace App\Filament\Admin\Pages;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Services\AbsensiRekapService;
use Filament\Pages\Page;
use Illuminate\Http\Request;

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


    public function mount(Request $request): void
    {
        $this->all_karyawan = Karyawan::get(['id_karyawan', 'nama']);
        $this->status_karyawan = $request->query('status_karyawan');



        $keyword = $request->query('karyawan_keyword');
        $this->selected_lokasi = $request->query('lokasi');
        $this->selected_proyek = $request->query('proyek');
        $this->show_all = $request->query('show_all') === '1';

        if ($keyword) {
            $matched = Karyawan::where('id_karyawan', $keyword)
                ->orWhere('nama', 'like', '%' . $keyword . '%')
                ->first();

            $this->selected_id = $matched?->id_karyawan;
            $this->selected_name = $matched?->nama;
            $this->status_karyawan = $matched?->status;
            $this->selected_lokasi = $matched?->lokasi;
            $this->selected_proyek = $matched?->jenis_proyek;
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
            ->distinct()
            ->pluck('jenis_proyek')
            ->filter()
            ->values()
            ->all();

        $this->loadRekap();
    }

    public function loadRekap(): void
    {
        if ($this->show_all) {
            $query = Absensi::whereBetween('tanggal', [$this->start_date, $this->end_date]);

            // Ambil semua nama karyawan sesuai filter
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

            $this->rekap = (new AbsensiRekapService())->rekapSemuaUser(
                $this->start_date,
                $this->end_date,
                $nama_karyawan
            );
        } elseif ($this->selected_name) {
            $this->rekap = (new AbsensiRekapService())->rekapUntukUser(
                $this->selected_name,
                $this->start_date,
                $this->end_date
            );

            $this->data_harian = Absensi::where('name', $this->selected_name)
                ->whereBetween('tanggal', [$this->start_date, $this->end_date])
                ->orderBy('tanggal')
                ->get();
        }
    }
}
