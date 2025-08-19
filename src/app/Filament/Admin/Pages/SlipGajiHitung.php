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
        $this->editingGajiId = request()->query('id');
        $this->karyawan_id   = request()->query('karyawan_id');
        $this->start_date    = request()->query('start_date');
        $this->end_date      = request()->query('end_date');

        if ($this->editingGajiId) {
            $gaji = Gaji::with('details')->findOrFail($this->editingGajiId);

            $this->karyawan_id = $gaji->id_karyawan;
            $this->start_date  = $gaji->periode_awal->format('Y-m-d');
            $this->end_date    = $gaji->periode_akhir->format('Y-m-d');

            $this->hitungGaji();
            $this->loadGajiData();
            if (! $this->hydrateKasbonFromSlipPayments((int) $this->editingGajiId)) {
                $this->computeKasbonAuto();
            }

            $this->calculateGrandTotal();
            return;
        }

        if ($this->start_date && $this->end_date && $this->karyawan_id) {
            $this->hitungGaji();
        }
    }


    public function hitungGaji()
    {
        $this->gaji_data = app(GajiService::class)->hitungGaji(
            $this->karyawan_id,
            $this->start_date,
            $this->end_date
        );

        $this->autoAddDefaultDeductions();
        $this->computeKasbonAuto();
        $this->calculateGrandTotal();
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
        // Pastikan data numerik valid
        foreach (['masuk', 'faktor', 'nominal_lembur'] as $field) {
            if (!is_numeric($this->newItem[$field])) {
                $this->newItem[$field] = 0;
            }
        }

        $this->validate([
            'newItem.type' => 'required|string',
            'newItem.masuk' => 'required|numeric|min:0',
            'newItem.faktor' => 'required|numeric|min:0',
            'newItem.nominal_lembur' => 'required|numeric|min:0',
        ]);

        $masuk = (float) $this->newItem['masuk'];
        $faktor = (float) $this->newItem['faktor'];
        $nominal = (float) $this->newItem['nominal_lembur'];
        $total = $masuk * $nominal;

        $this->newItem['total'] = $total;

        $itemTypes = [
            'uang_makan_lembur_malam' => ['keterangan' => 'Uang Makan Lembur Malam', 'no' => 'i'],
            'uang_makan_lembur_jalan' => ['keterangan' => 'Uang Makan Lembur Jalan', 'no' => 'j'],
            'bpjs_kesehatan' => ['keterangan' => 'Potongan BPJS Kesehatan', 'no' => 'k'],
            'bpjs_tk' => ['keterangan' => 'Potongan BPJS TK', 'no' => 'l'],
            'bpjs_gabungan' => ['keterangan' => 'Potongan BPJS Kesehatan + TK', 'no' => 'm']
        ];

        $type = $this->newItem['type'];
        if (empty($type)) {
            session()->flash('error', 'Silakan pilih jenis item');
            return;
        }

        $keterangan = $itemTypes[$type]['keterangan'];

        if (collect($this->additional_items)->contains('keterangan', $keterangan)) {
            session()->flash('error', 'Item ' . $keterangan . ' sudah ditambahkan');
            return;
        }

        $this->additional_items[] = [
            'no' => $itemTypes[$type]['no'],
            'keterangan' => $keterangan,
            'masuk' => $this->newItem['masuk'],
            'faktor' => $this->newItem['faktor'],
            'nominal_lembur' => $this->newItem['nominal_lembur'],
            'total' => $this->newItem['total'], 
    ];

        $this->newItem = [
            'type' => '',
            'masuk' => '',
            'faktor' => '',
            'nominal_lembur' => '',
            'total' => '',
        ];

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
        $nominals = $this->gaji_data['nominals'] ?? [];

        $this->newItem['nominal_lembur'] = $nominals[$value] ?? 0;
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
        $masuk = (float) ($this->newItem['masuk'] ?? 0);
        $nominal = (float) ($this->newItem['nominal_lembur'] ?? 0);
        $faktor = (float) ($this->newItem['faktor'] ?? 1);

        $this->newItem['total'] = $masuk * $nominal * $faktor;
    }

    public function simpanSlipGaji()
    {
        DB::beginTransaction();

        try {
            // Buat atau update header
            if ($this->editingGajiId) {
                $gaji = Gaji::findOrFail($this->editingGajiId);
                $gaji->update([
                    'id_karyawan' => $this->gaji_data['id_karyawan'],
                    'nama' => $this->gaji_data['nama'],
                    'status' => $this->gaji_data['status'],
                    'lokasi' => $this->gaji_data['lokasi'],
                    'jenis_proyek' => $this->gaji_data['jenis_proyek'],
                    'periode_awal' => $this->gaji_data['periode_awal'],
                    'periode_akhir' => $this->gaji_data['periode_akhir'],
                ]);

                // Hapus semua detail lama
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
                'keterangan' => 'Kasbon',
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

        // Hapus semua pembayaran yang memang dibuat oleh slip ini
        KasbonPayment::where('slip_gaji_id', $gaji->id)->delete();

        $slots = $this->overlappedHalfBoundaries();
        foreach ($this->kasbon_loans as $row) {
            $loanId = (int) ($row['loan_id'] ?? 0);
            $units  = (int) ($row['units']   ?? 0);
            $unit   = (float)($row['unit']    ?? 0);
            if ($loanId <= 0 || $units <= 0 || $unit <= 0) continue;

            $loan  = KasbonLoan::withSum('payments as payments_sum_nominal', 'nominal')->find($loanId);
            if (!$loan) continue;

            $saldo = max(0.0, (float)$loan->pokok - (float)($loan->payments_sum_nominal ?? 0));

            for ($i = 0; $i < $units && $i < count($slots) && $saldo > 0; $i++) {
                $pay  = min($unit, $saldo);
                $slot = $slots[$i];

                KasbonPayment::updateOrCreate(
                    [
                        'kasbon_loan_id' => $loanId,
                        'slip_gaji_id'   => $gaji->id,
                        'tanggal'        => $slot['date'],
                    ],
                    [
                        'nominal'       => $pay,
                        'sumber'        => 'slip',
                        'periode_label' => $slot['label'],
                        'catatan'       => 'Potongan otomatis dari slip gaji',
                    ]
                );

                $saldo -= $pay;
            }
        }
            DB::commit();
            return redirect()->route('filament.admin.pages.histori-slip-gaji');

            session()->flash('success', $this->editingGajiId ? 'Slip gaji berhasil diperbarui.' : 'Slip gaji berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan slip gaji: ' . $e->getMessage());
        }
    }

    private function loadExistingGaji($id): void
    {
        $gaji = Gaji::with('details')->findOrFail($id);

        $this->selected_id = $gaji->id_karyawan;
        $this->start_date = $gaji->periode_awal;
        $this->end_date = $gaji->periode_akhir;

        // Muat ulang gaji_data berdasarkan data tersimpan
        $this->gaji_data = [
            'id_karyawan' => $gaji->id_karyawan,
            'nama' => $gaji->nama,
            'status' => $gaji->status,
            'lokasi' => $gaji->lokasi,
            'jenis_proyek' => $gaji->jenis_proyek,
            'periode_awal' => $gaji->periode_awal,
            'periode_akhir' => $gaji->periode_akhir,
            // kasbon, total, dll nanti bisa ditarik dari detail
        ];

        foreach ($gaji->details as $detail) {
            switch ($detail->kode) {
                case 'a':
                    $this->gaji_data['gaji_setengah_bulan_nominal'] = $detail->total;
                    break;
                case 'b':
                case 'c':
                case 'd':
                case 'e':
                    $tipe = match ($detail->kode) {
                        'b' => 'senin_jumat',
                        'c' => 'sabtu',
                        'd' => 'minggu',
                        'e' => 'hari_besar',
                    };
                    $this->gaji_data["lembur_{$tipe}_masuk"] = $detail->masuk;
                    $this->gaji_data["lembur_{$tipe}_faktor"] = $detail->faktor;
                    $this->gaji_data["lembur_{$tipe}_nominal"] = $detail->nominal;
                    $this->gaji_data["lembur_{$tipe}_total"] = $detail->total;
                    break;
                case 'f':
                    $this->gaji_data['potongan_tidak_masuk_masuk'] = $detail->masuk;
                    $this->gaji_data['potongan_tidak_masuk_nominal'] = $detail->nominal;
                    $this->gaji_data['potongan_tidak_masuk_total'] = $detail->total;
                    break;
                case 'g':
                    $this->gaji_data['potongan_tidak_disiplin_masuk'] = $detail->masuk;
                    $this->gaji_data['potongan_tidak_disiplin_nominal'] = $detail->nominal;
                    $this->gaji_data['potongan_tidak_disiplin_total'] = $detail->total;
                    break;
                // case 'h':
                //     $this->gaji_data['kasbon_masuk'] = $detail->masuk;
                //     $this->gaji_data['kasbon_faktor'] = $detail->faktor;
                //     $this->gaji_data['kasbon_nominal'] = $detail->nominal;
                //     $this->gaji_data['kasbon'] = $detail->total;
                //     break;
                case 'jml':
                    $this->sub_total = $detail->total;
                    break;
                case 'grand':
                    $this->gaji_data['total_gaji'] = $detail->total;
                    break;
                default:
                    // Tambahan item manual
                    $this->additional_items[] = [
                        'no' => $detail->kode,
                        'keterangan' => $detail->keterangan,
                        'masuk' => $detail->masuk,
                        'faktor' => $detail->faktor,
                        'nominal_lembur' => $detail->nominal,
                        'total' => $detail->total,
                    ];
                    break;
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
        if (!$this->overlapsFirstHalf()) return;

        $nominals = $this->gaji_data['nominals'] ?? [];
        $has = fn($k) => isset($nominals[$k]) && (float)$nominals[$k] > 0;

        if ($has('bpjs_gabungan')) {
            $this->pushAdditionalItemIfMissing('bpjs_gabungan');
        } else {
            if ($has('bpjs_kesehatan')) $this->pushAdditionalItemIfMissing('bpjs_kesehatan');
            if ($has('bpjs_tk'))        $this->pushAdditionalItemIfMissing('bpjs_tk');
        }

        $this->calculateGrandTotal();
    }

    private function overlapsFirstHalf(): bool
    {
        if (!$this->start_date || !$this->end_date) return false;

        $start = Carbon::parse($this->start_date);
        $end   = Carbon::parse($this->end_date);

        if ($start->gt($end)) [$start, $end] = [$end, $start];

        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $halfStart = $cursor->copy()->startOfMonth();
            $halfEnd   = $cursor->copy()->startOfMonth()->day(15);

            // cek overlap dua rentang: [start,end] vs [halfStart,halfEnd]
            $overlap = $start->lte($halfEnd) && $end->gte($halfStart);
            if ($overlap) return true;

            $cursor->addMonth();
        }
        return false;
    }
    private function computeKasbonAuto(): void
    {
        $this->kasbon_loans = [];
        $this->gaji_data['kasbon']         = 0.0;
        $this->gaji_data['kasbon_masuk']   = 0;
        $this->gaji_data['kasbon_faktor']  = 1;
        $this->gaji_data['kasbon_nominal'] = 0.0;

        if (!$this->karyawan_id) return;

        $slots = $this->overlappedHalfBoundaries();
        $slotsCount = count($slots);
        if ($slotsCount === 0) return;

        $loans = \App\Models\KasbonLoan::query()
            ->where('karyawan_id', $this->karyawan_id)
            ->where('status', '!=', 'ditutup')
            ->withCount('payments')
            ->withSum('payments as payments_sum_nominal','nominal')
            ->get();

        $totalPotongan      = 0.0;
        $unitsAppliedTotal  = 0;

        foreach ($loans as $loan) {
            $paid  = (float)($loan->payments_sum_nominal ?? 0);
            $saldo = max(0.0, (float)$loan->pokok - $paid);
            $sisaX = max(0, (int)$loan->tenor - (int)$loan->payments_count);
            $unit  = (float)$loan->cicilan;

            if ($saldo <= 0 || $unit <= 0 || $sisaX <= 0) continue;

            $unitsToCharge = min($slotsCount, $sisaX);

            $amountThisLoan = 0.0;
            $applied = 0;
            for ($i = 0; $i < $unitsToCharge && $saldo > 0; $i++) {
                $pay = min($unit, $saldo);
                $amountThisLoan += $pay;
                $saldo -= $pay;
                $applied++;
            }

            if ($amountThisLoan <= 0) continue;

            $this->kasbon_loans[] = [
                'loan_id' => $loan->id,
                'unit'    => $unit,
                'units'   => $applied,
                'amount'  => $amountThisLoan,
                'sisa_x'  => $sisaX,
            ];

            $totalPotongan     += $amountThisLoan;
            $unitsAppliedTotal += $applied;
        }

        $this->gaji_data['kasbon']         = $totalPotongan;
        $this->gaji_data['kasbon_masuk']   = $unitsAppliedTotal;
        $this->gaji_data['kasbon_nominal'] = $totalPotongan;
    }

    private function kasbonPeriodeLabel(): string
    {
        $end = Carbon::parse($this->end_date ?? now());
        return $this->isFirstHalfPeriod()
            ? '01–15 '   . $end->copy()->startOfMonth()->format('M Y')
            : '16–Akhir ' . $end->copy()->startOfMonth()->format('M Y');
    }
    private function overlappedHalfBoundaries(): array
    {
        if (!$this->start_date || !$this->end_date) return [];

        $start = Carbon::parse($this->start_date);
        $end   = Carbon::parse($this->end_date);
        if ($start->gt($end)) [$start, $end] = [$end, $start];

        $cursor = $start->copy()->startOfMonth();
        $out = [];

        while ($cursor->lte($end)) {
            // 01–15
            $half1Start = $cursor->copy()->startOfMonth()->startOfDay();
            $half1End   = $cursor->copy()->day(15)->endOfDay();
            if ($start->lte($half1End) && $end->gte($half1Start)) {
                $out[] = [
                    'date'  => $half1End->toDateString(),
                    // ⬇️ label dibentuk manual
                    'label' => '01–15 ' . $half1Start->format('M Y'),
                ];
            }

            // 16–Akhir
            $half2Start = $cursor->copy()->day(16)->startOfDay();
            $half2End   = $cursor->copy()->endOfMonth()->endOfDay();
            if ($start->lte($half2End) && $end->gte($half2Start)) {
                $out[] = [
                    'date'  => $half2End->toDateString(),
                    // ⬇️ label dibentuk manual
                    'label' => '16–Akhir ' . $half2Start->format('M Y'),
                ];
            }

            $cursor->addMonth();
        }

        return $out;
    }

    private function hydrateKasbonFromSlipPayments(): bool
    {
        if (!$this->editingGajiId) return false;

        $pays = KasbonPayment::where('slip_gaji_id', $this->editingGajiId)->get();
        if ($pays->isEmpty()) return false;

        $this->kasbon_loans = [];
        $this->gaji_data['kasbon']         = 0.0;
        $this->gaji_data['kasbon_masuk']   = 0;
        $this->gaji_data['kasbon_faktor']  = 1;
        $this->gaji_data['kasbon_nominal'] = 0.0;

        foreach ($pays as $p) {
            $this->gaji_data['kasbon']         += (float)$p->nominal;
            $this->gaji_data['kasbon_nominal'] += (float)$p->nominal;
            $this->gaji_data['kasbon_masuk']   += 1;

            $this->kasbon_loans[] = [
                'loan_id' => $p->kasbon_loan_id,
                'unit'    => (float)$p->nominal, // untuk referensi
                'units'   => 1,
                'amount'  => (float)$p->nominal,
            ];
        }
        return true;
    }

}
