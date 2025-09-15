<?php
namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Karyawan;
use App\Services\GajiService;
use Illuminate\Http\Request;
use App\Models\Gaji;
use App\Models\GajiDetail;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;
use App\Models\KasbonLoan;
use App\Models\KasbonPayment;
use Illuminate\Support\Collection;
use App\Models\AbsensiRekap;
use Filament\Actions;

class SlipGajiHitung extends Page
{
    protected static ?string $navigationIcon = null;
    protected static string $view = 'filament.pages.slip-gaji-hitung';
    protected static ?string $navigationLabel = 'Slip Gaji';
    protected static ?string $title = 'Slip Gaji';
    protected static ?int $navigationSort = 3;
    public $lokasi_options;
    public $proyek_options;
    public ?string $selected_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?array $gaji_data = null;
    public $all_karyawan = null;
    public $additional_items = [];
    public $sub_total = 0;
    public ?string $editingGajiId = null;
    public ?string $karyawan_id = null;
    public array $kasbon_loans = [];
    public string $tipe_pembayaran = 'payroll';
    public $newItem = [
        'type' => '',
        'masuk' => '',
        'faktor' => '',
        'nominal_lembur' => '',
        'total' => ''
    ];

    protected $rules = [
        'newItem.type' => 'required|string',
        'newItem.masuk' => 'required|numeric|min:0',
        'newItem.faktor' => 'required|numeric|min:0',
        'newItem.nominal_lembur' => 'required|numeric|min:0',
        'newItem.total' => 'required|numeric|min:0',
    ];
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }

    public function mount(): void
    {
        $this->editingGajiId   = request()->query('id') ?? request()->query('gaji_id') ?? request()->query('record');
        $this->karyawan_id     = request()->query('karyawan_id');
        $this->start_date      = request()->query('start_date');
        $this->end_date        = request()->query('end_date');
        $this->tipe_pembayaran = request()->query('tipe_pembayaran', $this->tipe_pembayaran);

        if ($this->editingGajiId) {
            $gaji = \App\Models\Gaji::with('details')->findOrFail($this->editingGajiId);

            $this->karyawan_id     = $gaji->id_karyawan;               // tetap pakai kode utk UI
            $this->start_date      = $gaji->periode_awal->toDateString();
            $this->end_date        = $gaji->periode_akhir->toDateString();
            $this->tipe_pembayaran = $gaji->tipe_pembayaran ?? 'payroll';

            // ⬇️ hitung ulang → pasti ambil dari Rekap (strict)
            $this->gaji_data = app(\App\Services\GajiService::class)->hitungGaji(
                $this->karyawan_id, $this->start_date, $this->end_date, (int) $gaji->id
            );

            // bawa item manual lama saja (opsional)
            $this->additional_items = $this->onlyAdditionalItems($gaji);

            $this->autoAddDefaultDeductions();
            $this->computeKasbonAuto();
            $this->calculateGrandTotal();
            return;
        }

        if ($this->start_date && $this->end_date && $this->karyawan_id) {
            $this->hitungGaji(); // ini juga sudah via service
        }
    }



    private function rekapLebihBaruDariSlip(Gaji $gaji): bool
    {
        $start = \Carbon\Carbon::parse($gaji->periode_awal)->toDateString();
        $end   = \Carbon\Carbon::parse($gaji->periode_akhir)->toDateString();

        $emp = \App\Models\Karyawan::where('id_karyawan', $gaji->id_karyawan)->first()
            ?? \App\Models\Karyawan::find($gaji->id_karyawan);
        if (! $emp) return false;

        $ids = array_values(array_filter([(int) $emp->id, ctype_digit((string) $gaji->id_karyawan) ? (int) $gaji->id_karyawan : null]));

        $rekap = \App\Models\AbsensiRekap::whereIn('karyawan_id', $ids)
            ->whereDate('periode_awal', $start)
            ->whereDate('periode_akhir', $end)
            ->latest('updated_at')
            ->first();

        return $rekap ? $rekap->updated_at->gt($gaji->updated_at) : false;
    }

    private function onlyAdditionalItems(Gaji $gaji): array
    {
        $reserved = ['a','b','c','d','e','f','g','h','jml','grand'];
        return $gaji->details->filter(function ($d) use ($reserved) {
            return !in_array(strtolower($d->kode ?? ''), $reserved, true);
        })->map(fn($d) => [
            'no'             => $d->kode,
            'keterangan'     => $d->keterangan,
            'masuk'          => (float) ($d->masuk   ?? 0),
            'faktor'         => (float) ($d->faktor  ?? 0),
            'nominal_lembur' => (float) ($d->nominal ?? 0),
            'total'          => (float) ($d->total   ?? 0),
        ])->values()->all();
    }

    public function hitungGaji()
    {
        try {
            $this->gaji_data = app(\App\Services\GajiService::class)->hitungGaji(
                $this->karyawan_id, $this->start_date, $this->end_date
            );

            // $this->applyRekapToGajiData();
            // $this->autoAddDefaultDeductions();
            $this->computeKasbonAuto();
            $this->calculateGrandTotal();

        } catch (\DomainException $e) {
            // kosongkan tampilan slip
            $this->gaji_data = null;
            $this->additional_items = [];
            $this->sub_total = 0;

            session()->flash('error', $e->getMessage());
            return;
        }
    }
    public function syncFromRekap(): void
    {
        if (!$this->karyawan_id || !$this->start_date || !$this->end_date) return;

        $service = app(\App\Services\GajiService::class);

        // kirim slipId supaya kasbon yang sudah tertaut ikut dihitung
        $this->gaji_data = $service->hitungGaji(
            $this->karyawan_id,
            $this->start_date,
            $this->end_date,
            (int) $this->editingGajiId
        );

        // reset item manual & hitung ulang total
        $this->additional_items = [];
        $this->autoAddDefaultDeductions();
        $this->computeKasbonAuto();
        $this->calculateGrandTotal();

        session()->flash('success', 'Disinkronkan dari rekap terbaru.');
    }

    
    public function loadGajiData(): void
    {
        if (!$this->karyawan_id || !$this->start_date || !$this->end_date) {
            return;
        }

        $service = app(\App\Services\GajiService::class);
        $this->gaji_data = $service->hitungGaji(
            $this->karyawan_id,
            $this->start_date,
            $this->end_date
        );

        $this->autoAddDefaultDeductions();
        $this->computeKasbonAuto();
        $this->calculateGrandTotal();
    }

    public function hitungSlipGaji(): void
    {
        if ($this->selected_id && $this->start_date && $this->end_date) {
            $gajiService = new GajiService();
            $this->gaji_data = $gajiService->hitungGaji(
                $this->selected_id,
                $this->start_date,
                $this->end_date
            );

            // Add additional items to total
            $total_additional = 0;
            foreach ($this->additional_items as $item) {
                if (str_contains(strtolower($item['keterangan']), 'potongan')) {
                    $total_additional -= $item['total'];
                } else {
                    $total_additional += $item['total'];
                }
            }
            
            // $this->gaji_data['total_gaji'] += $total_additional;
            $this->calculateGrandTotal();
        }
    }

    public function addItem()
    {
        $this->normalizeNewItemNumbers();
        foreach (['masuk','faktor','nominal_lembur'] as $f) {
            if (!is_numeric($this->newItem[$f])) $this->newItem[$f] = 0;
        }

        $this->validate([
            'newItem.type' => 'required|string',
            'newItem.masuk' => 'required|numeric|min:0',
            'newItem.faktor' => 'required|numeric|min:0',
            'newItem.nominal_lembur' => 'required|numeric|min:0',
        ]);

        $masuk   = (float) $this->newItem['masuk'];
        $faktor  = (float) $this->newItem['faktor'];
        $nominal = (float) $this->newItem['nominal_lembur'];

        // ⬅️ perbaiki: total pakai faktor
        $this->newItem['total'] = $masuk * $nominal * max(1, $faktor);

        // ⬇️ TAMBAH tipe “Perizinan”
        $itemTypes = [
            'uang_makan_lembur_malam' => ['keterangan' => 'Uang Makan Lembur Malam',      'no' => 'i'],
            'uang_makan_lembur_jalan' => ['keterangan' => 'Uang Makan Lembur Jalan',      'no' => 'j'],

            'bpjs_kesehatan' => ['keterangan' => 'Potongan BPJS Kesehatan',               'no' => 'k'],
            'bpjs_tk'        => ['keterangan' => 'Potongan BPJS TK',                      'no' => 'l'],
            'bpjs_gabungan'  => ['keterangan' => 'Potongan BPJS Kesehatan + TK',          'no' => 'm'],

            'perizinan_sakit'         => ['keterangan' => 'Perizinan Sakit (Surat Dokter)',       'no' => 'n'],
            'perizinan_berduka'       => ['keterangan' => 'Perizinan Berduka',                    'no' => 'o'],
            'perizinan_tanpa_alasan'  => ['keterangan' => 'Potongan Perizinan Tanpa Alasan (8 jam/hari)', 'no' => 'p'],

        ];

        $type = $this->newItem['type'] ?? '';
        if (!$type || !isset($itemTypes[$type])) {
            session()->flash('error', 'Silakan pilih jenis item yang valid');
            return;
        }

        $keterangan = $itemTypes[$type]['keterangan'];
        if (collect($this->additional_items)->contains('keterangan', $keterangan)) {
            session()->flash('error', 'Item ' . $keterangan . ' sudah ditambahkan');
            return;
        }

        $this->additional_items[] = [
            'no'             => $itemTypes[$type]['no'],
            'keterangan'     => $keterangan,
            'masuk'          => $this->newItem['masuk'],
            'faktor'         => $this->newItem['faktor'],
            'nominal_lembur' => $this->newItem['nominal_lembur'],
            'total'          => $this->newItem['total'],
        ];

        $this->newItem = ['type'=>'','masuk'=>'','faktor'=>'','nominal_lembur'=>'','total'=>''];
        $this->calculateGrandTotal();
    }

    
    public function deleteItem($index)
    {
        unset($this->additional_items[$index]);

        $this->additional_items = array_values($this->additional_items);
        $this->hitungSlipGaji();
        $this->calculateGrandTotal();
    }

    private function calculateGrandTotal()
    {
        $this->sub_total = $this->calculateSubTotal();
        $kasbon = $this->gaji_data['kasbon'] ?? 0;

        $this->gaji_data['total_gaji'] = $this->sub_total - $kasbon;
    }
    private function calculateSubTotal()
    {
        $status = strtolower($this->gaji_data['status'] ?? '');
        $subTotal = 0;

        if ($status === 'harian lepas') {
            $subTotal +=
                ($this->gaji_data['gaji_harian_masuk'] ?? 0) *
                ($this->gaji_data['gaji_harian_nominal'] ?? 0);
        } else {
            $subTotal += $this->gaji_data['gaji_setengah_bulan_nominal'] ?? 0;
        }

        $subTotal += $this->gaji_data['lembur_senin_jumat_total'] ?? 0;
        $subTotal += $this->gaji_data['lembur_sabtu_total'] ?? 0;
        $subTotal += $this->gaji_data['lembur_minggu_total'] ?? 0;
        $subTotal += $this->gaji_data['lembur_hari_besar_total'] ?? 0;

        $subTotal -= $this->gaji_data['potongan_tidak_masuk_total'] ?? 0;
        $subTotal -= $this->gaji_data['potongan_tidak_disiplin_total'] ?? 0;

        foreach ($this->additional_items as $item) {
            if (str_contains(strtolower($item['keterangan']), 'potongan')) {
                $subTotal -= $item['total'];
            } else {
                $subTotal += $item['total'];
            }
        }
        return $subTotal;
    }
    public function updatedNewItemType($value)
    {
        if (in_array($value, ['perizinan_sakit','perizinan_berduka','perizinan_tanpa_alasan'], true)) {
            // Perizinan: nominal default 0
            $this->newItem['nominal_lembur'] = 0.0;
            if (!is_numeric($this->newItem['faktor']) || (float)$this->newItem['faktor'] <= 0) {
                $this->newItem['faktor'] = 1; // default 1, boleh diubah (mis. 8 utk potong per hari)
            }
        } else {
            // Item lain boleh tetap dari mapping karyawan
            $this->newItem['nominal_lembur'] = (float)($this->gaji_data['nominals'][$value] ?? 0);
        }

        $this->recalculateTotal();
    }
    public function updatedNewItemMasuk()
    {
        $this->recalculateTotal();
    }

    public function updatedNewItemNominalLembur()
    {
        $this->recalculateTotal();
    }

    private function recalculateTotal()
    {
        $this->normalizeNewItemNumbers();
        $masuk = (float) ($this->newItem['masuk'] ?? 0);
        $nominal = (float) ($this->newItem['nominal_lembur'] ?? 0);
        $faktor = (float) ($this->newItem['faktor'] ?? 1);

        $this->newItem['total'] = $masuk * $nominal * $faktor;
    }

    public function simpanSlipGaji()
    {
        DB::beginTransaction();

        try {
            $payload = [
                'id_karyawan'      => $this->gaji_data['id_karyawan'],
                'nama'             => $this->gaji_data['nama'],
                'status'           => $this->gaji_data['status'],
                'lokasi'           => $this->gaji_data['lokasi'],
                'jenis_proyek'     => $this->gaji_data['jenis_proyek'],
                'periode_awal'     => $this->gaji_data['periode_awal'],
                'periode_akhir'    => $this->gaji_data['periode_akhir'],
                'tipe_pembayaran'  => $this->tipe_pembayaran,   // ⬅️ penting
            ];

            if ($this->editingGajiId) {
                $gaji = Gaji::findOrFail($this->editingGajiId);
                $gaji->update($payload);

                $gaji->details()->delete();
            } else {
                $gaji = Gaji::create([
                    'id_karyawan' => $this->gaji_data['id_karyawan'],
                    'nama' => $this->gaji_data['nama'],
                    'status' => $this->gaji_data['status'],
                    'lokasi' => $this->gaji_data['lokasi'],
                    'jenis_proyek' => $this->gaji_data['jenis_proyek'],
                    'periode_awal' => $this->gaji_data['periode_awal'],
                    'periode_akhir' => $this->gaji_data['periode_akhir'],
                    'tipe_pembayaran' => $this->tipe_pembayaran ?? 'payroll',
                ]);
            }
            GajiDetail::create([
                'gaji_id' => $gaji->id,
                'kode' => 'a',
                'keterangan' => $this->gaji_data['status'] === 'harian lepas' ? 'Gaji Harian' : 'Gaji Setengah bln',
                'masuk' => $this->gaji_data['status'] === 'harian lepas' ? ($this->gaji_data['gaji_harian_masuk'] ?? 0) : null,
                'faktor' => null,
                'nominal' => $this->gaji_data['status'] === 'harian lepas'
                    ? ($this->gaji_data['gaji_harian_nominal'] ?? 0)
                    : ($this->gaji_data['gaji_setengah_bulan_nominal'] ?? 0),
                'total' => $this->gaji_data['status'] === 'harian lepas'
                    ? (($this->gaji_data['gaji_harian_masuk'] ?? 0) * ($this->gaji_data['gaji_harian_nominal'] ?? 0))
                    : ($this->gaji_data['gaji_setengah_bulan_nominal'] ?? 0),
            ]);

            $lemburRows = [
                ['kode' => 'b', 'tipe' => 'senin_jumat', 'label' => 'Lembur Senin s/d Jumat'],
                ['kode' => 'c', 'tipe' => 'sabtu', 'label' => 'Lembur Sabtu'],
                ['kode' => 'd', 'tipe' => 'minggu', 'label' => 'Lembur Minggu'],
                ['kode' => 'e', 'tipe' => 'hari_besar', 'label' => 'Lembur Hari Besar'],
            ];

            foreach ($lemburRows as $row) {
                GajiDetail::create([
                    'gaji_id' => $gaji->id,
                    'kode' => $row['kode'],
                    'keterangan' => $row['label'],
                    'masuk' => $this->gaji_data["lembur_{$row['tipe']}_masuk"],
                    'faktor' => $this->gaji_data["lembur_{$row['tipe']}_faktor"],
                    'nominal' => $this->gaji_data["lembur_{$row['tipe']}_nominal"],
                    'total' => $this->gaji_data["lembur_{$row['tipe']}_total"],
                ]);
            }

            // Tambahan manual
            foreach ($this->additional_items as $item) {
                GajiDetail::create([
                    'gaji_id' => $gaji->id,
                    'kode' => $item['no'],
                    'keterangan' => $item['keterangan'],
                    'masuk' => $item['masuk'],
                    'faktor' => $item['faktor'],
                    'nominal' => $item['nominal_lembur'],
                    'total' => $item['total'],
                ]);
            }

            // f - Potongan Tidak Masuk
            GajiDetail::create([
                'gaji_id' => $gaji->id,
                'kode' => 'f',
                'keterangan' => 'Potongan Gaji Tdk Masuk (Perjam)',
                'masuk' => $this->gaji_data['potongan_tidak_masuk_masuk'],
                'faktor' => null,
                'nominal' => $this->gaji_data['potongan_tidak_masuk_nominal'],
                'total' => $this->gaji_data['potongan_tidak_masuk_total'],
            ]);

            // g - Potongan Tidak Disiplin
            GajiDetail::create([
                'gaji_id' => $gaji->id,
                'kode' => 'g',
                'keterangan' => 'Potongan Gaji Tdk Disiplin',
                'masuk' => $this->gaji_data['potongan_tidak_disiplin_masuk'],
                'faktor' => null,
                'nominal' => $this->gaji_data['potongan_tidak_disiplin_nominal'],
                'total' => $this->gaji_data['potongan_tidak_disiplin_total'],
            ]);

            // jml - Subtotal
            GajiDetail::create([
                'gaji_id' => $gaji->id,
                'kode' => 'jml',
                'keterangan' => 'Jumlah (Subtotal)',
                'masuk' => null,
                'faktor' => null,
                'nominal' => null,
                'total' => $this->sub_total,
            ]);

            // h - Kasbon
            GajiDetail::create([
                'gaji_id' => $gaji->id,
                'kode' => 'h',
                'keterangan' => 'Kasbon (otomatis)',
                'masuk' => $this->gaji_data['kasbon_masuk'] ?? 0,
                'faktor' => $this->gaji_data['kasbon_faktor'] ?? 1,
                'nominal' => $this->gaji_data['kasbon_nominal'] ?? 0,
                'total' => $this->gaji_data['kasbon'] ?? 0,
            ]);

            // grand - Grand Total
            GajiDetail::create([
                'gaji_id' => $gaji->id,
                'kode' => 'grand',
                'keterangan' => 'Grand Total',
                'masuk' => null,
                'faktor' => null,
                'nominal' => null,
                'total' => $this->gaji_data['total_gaji'],
            ]);

            $end = \Carbon\Carbon::parse($this->end_date ?? now());
            if ((int) $end->day <= 15) {
                $halfEnd = $end->copy()->startOfMonth()->day(15);
                $label   = $end->copy()->startOfMonth()->format('01–15 M Y');
            } else {
                $halfEnd = $end->copy()->endOfMonth();
                $label   = $end->copy()->startOfMonth()->format('16–Akhir M Y');
            }
            $tglSlip = $halfEnd->toDateString();

        // tautkan payment YANG SUDAH ADA (belum tertaut) untuk periode ini
        $awal  = \Carbon\Carbon::parse($this->start_date)->toDateString();
        $akhir = \Carbon\Carbon::parse($this->end_date)->toDateString();
        $empPk = $this->loanKaryawanId();

        if ($empPk) {
            KasbonPayment::query()
                ->whereHas('loan', fn($q) => $q->where('karyawan_id', $empPk))
                ->whereBetween('tanggal', [$awal, $akhir])
                ->where('sumber','slip')
                ->whereNull('slip_gaji_id')
                ->update([
                    'slip_gaji_id'  => $gaji->id,
                    'periode_label' => $this->kasbonPeriodeLabel(),
                ]);
        }


        // $slots = $this->overlappedHalfBoundaries();
        foreach ($this->kasbon_loans as $row) {
            $loanId = (int) ($row['loan_id'] ?? 0);
            $units  = (int) ($row['units']   ?? 0);
            $unit   = (float)($row['unit']    ?? 0);
            $loanSlots = $row['slots'] ?? [];
            if ($loanId <= 0 || $units <= 0 || $unit <= 0) continue;

            $loan  = KasbonLoan::withSum('payments as payments_sum_nominal', 'nominal')->find($loanId);
            if (!$loan) continue;

            $saldo = max(0.0, (float)$loan->pokok - (float)($loan->payments_sum_nominal ?? 0));

            for ($i = 0; $i < $units && $i < count($loanSlots) && $saldo > 0; $i++) {
                $pay  = min($unit, $saldo);
                $slot = $loanSlots[$i];

                KasbonPayment::updateOrCreate(
                    [
                        'kasbon_loan_id' => $loanId,
                        'tanggal'        => $slot['date'], // ← cocokkan ke data lama yang mungkin sudah ada (slip_gaji_id null)
                    ],
                    [
                        'slip_gaji_id'   => $gaji->id,
                        'nominal'        => $pay,
                        'sumber'         => 'slip',
                        'periode_label'  => $slot['label'],
                        'catatan'        => 'Potongan otomatis dari slip gaji',
                    ]
                );
                $saldo -= $pay;
            }
        }
            DB::commit();
                session()->flash('success', $this->editingGajiId ? 'Slip gaji berhasil diperbarui.' : 'Slip gaji berhasil disimpan.');
                return redirect()->route('filament.admin.pages.histori-slip-gaji');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan slip gaji: ' . $e->getMessage());
        }
    }

// ...
    private function fetchExistingKasbonPaymentsInRange(): Collection
    {
        if (!$this->karyawan_id || !$this->start_date || !$this->end_date) {
            return collect();
        }

        $empPk = $this->loanKaryawanId(); // PK karyawan pada KasbonLoan
        if (!$empPk) return collect();

        $start = \Carbon\Carbon::parse($this->start_date)->toDateString();
        $end   = \Carbon\Carbon::parse($this->end_date)->toDateString();

        return \App\Models\KasbonPayment::query()
            ->whereHas('loan', fn($q) => $q->where('karyawan_id', $empPk))
            ->whereBetween('tanggal', [$start, $end])
            ->where('sumber', 'slip')
            ->when(
                $this->editingGajiId,
                fn($q) => $q->where(fn($qq) => $qq->whereNull('slip_gaji_id')->orWhere('slip_gaji_id', $this->editingGajiId)),
                fn($q) => $q->whereNull('slip_gaji_id')
            )
            ->orderBy('tanggal')
            ->get();
    }

    private function loadExistingGaji($id): void
    {
        $gaji = Gaji::with('details')->findOrFail($id);

        $this->selected_id = $gaji->id_karyawan;
        $this->start_date  = $gaji->periode_awal;   // kalau mau string: ->toDateString()
        $this->end_date    = $gaji->periode_akhir;

        $this->gaji_data = [
            'id_karyawan'   => $gaji->id_karyawan,
            'nama'          => $gaji->nama,
            'status'        => $gaji->status,
            'lokasi'        => $gaji->lokasi,
            'jenis_proyek'  => $gaji->jenis_proyek,
            'periode_awal'  => $gaji->periode_awal,
            'periode_akhir' => $gaji->periode_akhir,
        ];

        // ⬇️ penting: default semua field supaya nggak “undefined”
        $defaults = [
            'gaji_harian_masuk' => 0, 'gaji_harian_nominal' => 0, 'gaji_setengah_bulan_nominal' => 0,

            'lembur_senin_jumat_masuk' => 0, 'lembur_senin_jumat_faktor' => 0, 'lembur_senin_jumat_nominal' => 0, 'lembur_senin_jumat_total' => 0,
            'lembur_sabtu_masuk'       => 0, 'lembur_sabtu_faktor'       => 0, 'lembur_sabtu_nominal'       => 0, 'lembur_sabtu_total'       => 0,
            'lembur_minggu_masuk'      => 0, 'lembur_minggu_faktor'      => 0, 'lembur_minggu_nominal'      => 0, 'lembur_minggu_total'      => 0,
            'lembur_hari_besar_masuk'  => 0, 'lembur_hari_besar_faktor'  => 0, 'lembur_hari_besar_nominal'  => 0, 'lembur_hari_besar_total'  => 0,

            'potongan_tidak_masuk_masuk'     => 0, 'potongan_tidak_masuk_nominal'     => 0, 'potongan_tidak_masuk_total'     => 0,
            'potongan_tidak_disiplin_masuk'  => 0, 'potongan_tidak_disiplin_nominal'  => 0, 'potongan_tidak_disiplin_total'  => 0,
            'kasbon' => 0, 'kasbon_masuk' => 0, 'kasbon_faktor' => 1, 'kasbon_nominal' => 0,
        ];
        $this->gaji_data = $this->gaji_data + $defaults;

        foreach ($gaji->details as $detail) {
            switch ($detail->kode) {
                case 'a':
                    $status = strtolower($gaji->status ?? '');

                    if ($status === 'harian lepas') {
                        $this->gaji_data['gaji_harian_masuk']   = (float) ($detail->masuk   ?? 0);
                        $this->gaji_data['gaji_harian_nominal'] = (float) ($detail->nominal ?? 0);
                        
                    } else {
                        $this->gaji_data['gaji_setengah_bulan_nominal'] =
                            (float) ($detail->nominal ?? $detail->total ?? 0);
                    }
                    break;

                case 'b': case 'c': case 'd': case 'e':
                    $tipe = match ($detail->kode) {
                        'b' => 'senin_jumat',
                        'c' => 'sabtu',
                        'd' => 'minggu',
                        'e' => 'hari_besar',
                    };
                    $this->gaji_data["lembur_{$tipe}_masuk"]   = (float) ($detail->masuk   ?? 0);
                    $this->gaji_data["lembur_{$tipe}_faktor"]  = (float) ($detail->faktor  ?? 0);
                    $this->gaji_data["lembur_{$tipe}_nominal"] = (float) ($detail->nominal ?? 0);
                    $this->gaji_data["lembur_{$tipe}_total"]   = (float) ($detail->total   ?? 0);
                    break;

                case 'f':
                    $this->gaji_data['potongan_tidak_masuk_masuk']   = (float) ($detail->masuk   ?? 0);
                    $this->gaji_data['potongan_tidak_masuk_nominal'] = (float) ($detail->nominal ?? 0);
                    $this->gaji_data['potongan_tidak_masuk_total']   = (float) ($detail->total   ?? 0);
                    break;

                case 'g':
                    $this->gaji_data['potongan_tidak_disiplin_masuk']   = (float) ($detail->masuk   ?? 0);
                    $this->gaji_data['potongan_tidak_disiplin_nominal'] = (float) ($detail->nominal ?? 0);
                    $this->gaji_data['potongan_tidak_disiplin_total']   = (float) ($detail->total   ?? 0);
                    break;

                case 'h':
                    $this->gaji_data['kasbon_masuk']   = (int)   ($detail->masuk   ?? 0);
                    $this->gaji_data['kasbon_faktor']  = (float) ($detail->faktor  ?? 1);
                    $this->gaji_data['kasbon_nominal'] = (float) ($detail->nominal ?? 0);
                    $this->gaji_data['kasbon']         = (float) ($detail->total   ?? 0);
                    break;

                case 'jml':
                    $this->sub_total = (float) ($detail->total ?? 0);
                    break;

                case 'grand':
                    $this->gaji_data['total_gaji'] = (float) ($detail->total ?? 0);
                    break;

                default:
                    $this->additional_items[] = [
                        'no'             => $detail->kode,
                        'keterangan'     => $detail->keterangan,
                        'masuk'          => (float) ($detail->masuk   ?? 0),
                        'faktor'         => (float) ($detail->faktor  ?? 0),
                        'nominal_lembur' => (float) ($detail->nominal ?? 0),
                        'total'          => (float) ($detail->total   ?? 0),
                    ];
                    break;
            }
        }
    }
    private function normalizeNewItemNumbers(): void
    {
        foreach (['masuk','faktor','nominal_lembur','total'] as $k) {
            if (isset($this->newItem[$k]) && is_string($this->newItem[$k])) {
                // cuma ganti desimal koma -> titik
                $this->newItem[$k] = str_replace(',', '.', $this->newItem[$k]);
            }
        }
    }

    protected function getViewData(): array
    {
        return [
            'editingGajiId' => $this->editingGajiId,
        ];
    }
    private function isFirstHalfPeriod(): bool
    {
        if (!$this->start_date || !$this->end_date) return false;
        $awal  = Carbon::parse($this->start_date);
        $akhir = Carbon::parse($this->end_date);

        return $awal->day === 1
            && $akhir->day === 15
            && $awal->month === $akhir->month
            && $awal->year === $akhir->year;
    }
    private function pushAdditionalItemIfMissing(string $type, int $qty = 1, float $faktor = 1.0): void
    {
        $itemTypes = [
            'bpjs_kesehatan' => ['keterangan' => 'Potongan BPJS Kesehatan',      'no' => 'k'],
            'bpjs_tk'        => ['keterangan' => 'Potongan BPJS TK',             'no' => 'l'],
            'bpjs_gabungan'  => ['keterangan' => 'Potongan BPJS Kesehatan + TK', 'no' => 'm'],
        ];

        if (!isset($itemTypes[$type])) return;

        $nominals = $this->gaji_data['nominals'] ?? [];
        $nominal  = (float)($nominals[$type] ?? 0);

        if ($nominal <= 0) return; // hanya yang punya harga

        $keterangan = $itemTypes[$type]['keterangan'];

        if (collect($this->additional_items)->contains('keterangan', $keterangan)) return;

        $this->additional_items[] = [
            'no'             => $itemTypes[$type]['no'],
            'keterangan'     => $keterangan,
            'masuk'          => $qty,
            'faktor'         => $faktor,
            'nominal_lembur' => $nominal,
            'total'          => $qty * $nominal * $faktor,
        ];
    }
    private function autoAddDefaultDeductions(): void
    {
        // hanya kalau periode MENGANDUNG tanggal 1
        if (!$this->rangeIncludesDayOfMonth(1)) {
            $this->removeBpjsAutoItems();
            return;
        }

        $nominals = $this->gaji_data['nominals'] ?? [];
        $has = fn($k) => isset($nominals[$k]) && (float)$nominals[$k] > 0;

        // selalu bersihkan dulu agar tidak dobel saat edit
        $this->removeBpjsAutoItems();

        if ($has('bpjs_gabungan')) {
            // push gabungan saja
            $this->pushAdditionalItemIfMissing('bpjs_gabungan');
        } else {
            if ($has('bpjs_kesehatan')) $this->pushAdditionalItemIfMissing('bpjs_kesehatan');
            if ($has('bpjs_tk'))        $this->pushAdditionalItemIfMissing('bpjs_tk');
        }

        $this->calculateGrandTotal();
    }


    private function rangeIncludesDayOfMonth(int $day): bool
    {
        if (!$this->start_date || !$this->end_date) return false;

        $start = \Carbon\Carbon::parse($this->start_date)->startOfDay();
        $end   = \Carbon\Carbon::parse($this->end_date)->endOfDay();
        if ($start->gt($end)) [$start, $end] = [$end, $start];

        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $d = $cursor->copy()->day($day)->startOfDay();
            if ($d->betweenIncluded($start, $end)) return true; // periode mencakup tgl {day}
            $cursor->addMonth();
        }
        return false;
    }

    private function removeBpjsAutoItems(): void
    {
        $labels = [
            'Potongan BPJS Kesehatan',
            'Potongan BPJS TK',
            'Potongan BPJS Kesehatan + TK',
        ];
        $this->additional_items = collect($this->additional_items)
            ->reject(function ($it) use ($labels) {
                $kode = strtolower(trim($it['no'] ?? ''));
                if (in_array($kode, ['k','l','m'], true)) {
                    return true; // buang by KODE
                }

                $ket  = strtolower(trim($it['keterangan'] ?? ''));
                // buang by LABEL (longgar: lowercase + trim)
                return in_array($ket, $labels, true);
            })
            ->values()
            ->all();

        $this->calculateGrandTotal();
    }
    private function computeKasbonAuto(): void
    {
        $this->kasbon_loans = [];
        $this->gaji_data['kasbon']         = 0.0;
        $this->gaji_data['kasbon_masuk']   = 0;
        $this->gaji_data['kasbon_faktor']  = 1;
        $this->gaji_data['kasbon_nominal'] = 0.0;

        // 1) Coba tarik Payment yang SUDAH ADA di DB untuk periode ini
        $existing = $this->fetchExistingKasbonPaymentsInRange();
        if ($existing->isNotEmpty()) {
            foreach ($existing as $p) {
                $this->gaji_data['kasbon']         += (float)$p->nominal;
                $this->gaji_data['kasbon_nominal'] += (float)$p->nominal;
                $this->gaji_data['kasbon_masuk']   += 1;
                $this->kasbon_loans[] = [
                    'loan_id' => $p->kasbon_loan_id,
                    'unit'    => (float)$p->nominal,
                    'units'   => 1,
                    'amount'  => (float)$p->nominal,
                    'slots'   => [[
                        'date'  => \Carbon\Carbon::parse($p->tanggal)->toDateString(),
                        'label' => $p->periode_label ?? $this->kasbonPeriodeLabel(),
                    ]],
                ];
            }
            return; // sudah ketemu; tidak perlu hitung otomatis
        }

        // 2) Jika belum ada payment, baru pakai kalkulasi otomatis (logika kamu yang lama)
        // --- kode kamu yang lama mulai dari sini, tidak diubah ---
        $this->gaji_data['kasbon']         = 0.0;
        $this->gaji_data['kasbon_masuk']   = 0;
        $this->gaji_data['kasbon_faktor']  = 1;
        $this->gaji_data['kasbon_nominal'] = 0.0;

        if (!$this->karyawan_id) return;

        $slots = $this->overlappedPaydayBoundaries();
        if (count($slots) === 0) return;

        $loanKaryawanId = $this->loanKaryawanId() ?? $this->karyawan_id;

        $loans = \App\Models\KasbonLoan::query()
            ->where('karyawan_id', $loanKaryawanId)
            ->where('status', '!=', 'ditutup')
            ->withCount('payments')
            ->withSum('payments as payments_sum_nominal','nominal')
            ->get();

        $total = 0.0; $unitsTotal = 0;

        foreach ($loans as $loan) {
            $paid  = (float)($loan->payments_sum_nominal ?? 0);
            $saldo = max(0.0, (float)$loan->pokok - $paid);
            $sisaX = max(0, (int)$loan->tenor - (int)$loan->payments_count);
            $unit  = (float)$loan->cicilan;
            if ($saldo <= 0 || $unit <= 0 || $sisaX <= 0) continue;

            $first = $this->firstBoundaryAfterLoan(\Carbon\Carbon::parse($loan->tanggal));
            $eligible = array_values(array_filter(
                $slots,
                fn($s) => \Carbon\Carbon::parse($s['date'])->gte($first)
            ));
            if (count($eligible) === 0) continue;

            $applied = 0; $amount = 0.0; $chosen = [];
            for ($i = 0; $i < min(count($eligible), $sisaX) && $saldo > 0; $i++) {
                $pay = min($unit, $saldo);
                $amount += $pay; $saldo -= $pay; $applied++;
                $chosen[] = $eligible[$i];
            }
            if ($amount <= 0) continue;

            $this->kasbon_loans[] = [
                'loan_id' => $loan->id,
                'unit'    => $unit,
                'units'   => $applied,
                'amount'  => $amount,
                'slots'   => $chosen,
            ];

            $total += $amount; $unitsTotal += $applied;
        }

        $this->gaji_data['kasbon']         = $total;
        $this->gaji_data['kasbon_masuk']   = $unitsTotal;
        $this->gaji_data['kasbon_nominal'] = $total;
    }
    private function firstBoundaryAfterLoan(\Carbon\Carbon $loanDate): \Carbon\Carbon
    {
        $payday = $this->kasbonPayday(); // 1 atau 16
        $sameMonthBoundary = $loanDate->copy()->day($payday)->startOfDay();

        // kalau loan dibuat SEBELUM boundary bulan itu → pakai boundary bulan itu,
        // kalau loan dibuat PADA/SETELAH boundary → pakai boundary bulan berikutnya
        return $loanDate->lt($sameMonthBoundary)
            ? $sameMonthBoundary
            : $sameMonthBoundary->copy()->addMonthNoOverflow();
    }

    private function overlappedPaydayBoundaries(): array
    {
        if (!$this->start_date || !$this->end_date) return [];

        $start  = \Carbon\Carbon::parse($this->start_date)->startOfDay();
        $end    = \Carbon\Carbon::parse($this->end_date)->endOfDay();
        if ($start->gt($end)) [$start, $end] = [$end, $start];

        $payday = $this->kasbonPayday(); // 1 atau 16
        $cursor = $start->copy()->startOfMonth();
        $out    = [];

        while ($cursor->lte($end)) {
            $boundary = $cursor->copy()->day($payday)->startOfDay();
            if ($boundary->betweenIncluded($start, $end)) {
                $label = ($payday === 1)
                    ? '01–15 '   . $boundary->format('M Y')
                    : '16–Akhir ' . $boundary->format('M Y');

                $out[] = [
                    'date'  => $boundary->toDateString(), // dicatat PERSIS di pay-day
                    'label' => $label,
                ];
            }
            $cursor->addMonth();
        }

        return $out;
    }

    private function kasbonPayday(): int
    {
        // 1 atau 16
        return 1; // ← ubah ke 16 jika pay-day perusahaan tanggal 16
    }


    private function kasbonPeriodeLabel(): string
    {
        $end = Carbon::parse($this->end_date ?? now());
        return $this->isFirstHalfPeriod()
            ? '01–15 '   . $end->copy()->startOfMonth()->format('M Y')
            : '16–Akhir ' . $end->copy()->startOfMonth()->format('M Y');
    }
    private function loanKaryawanId(): ?int
    {
        $emp = \App\Models\Karyawan::where('id_karyawan', $this->karyawan_id)->first();
        return $emp?->id;
    }
    private function clearSlip()
    {
        $this->gaji_data = null;
        $this->additional_items = [];
        $this->sub_total = 0;
    }
    public function updatedStartDate()
    {
        $this->clearSlip();
        $this->autoAddDefaultDeductions();
        $this->calculateGrandTotal();
    }

    public function updatedEndDate()
    {
        $this->clearSlip();
        $this->autoAddDefaultDeductions();
        $this->calculateGrandTotal();
    }
    
}