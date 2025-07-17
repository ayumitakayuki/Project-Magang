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
        'newItem.faktor' => 'required|numeric|min:0', // ✅ ini betul
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
        $this->karyawan_id = request()->query('karyawan_id');
        $this->start_date = request()->query('start_date');
        $this->end_date = request()->query('end_date');

        if ($this->editingGajiId) {
            $gaji = Gaji::with('details')->findOrFail($this->editingGajiId);

            $this->karyawan_id = $gaji->id_karyawan;
            $this->start_date = $gaji->periode_awal->format('Y-m-d');
            $this->end_date = $gaji->periode_akhir->format('Y-m-d');

            // pastikan perhitungan dijalankan ulang
            $this->hitungGaji();
            $this->loadGajiData(); // jika perlu muat data detail juga
        } elseif ($this->start_date && $this->end_date && $this->karyawan_id) {
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

        $this->calculateGrandTotal();
    }

    public function loadGajiData()
    {
        if (!request()->has(['karyawan_id', 'start_date', 'end_date'])) {
            return;
        }

        $service = new GajiService();
        $this->gaji_data = $service->hitungGaji(
            request()->query('karyawan_id'),
            $this->start_date,
            $this->end_date
        );
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
            
            $this->gaji_data['total_gaji'] += $total_additional;
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

        // Reset input
        $this->newItem = [
            'type' => '',
            'masuk' => '',
            'faktor' => '',
            'nominal_lembur' => '',
            'total' => '',
        ];

        $this->hitungSlipGaji();
        $this->calculateGrandTotal();
    }



    public function deleteItem($index)
    {
        unset($this->additional_items[$index]);

        $this->additional_items = array_values($this->additional_items);
        $this->hitungSlipGaji();
        $this->calculateGrandTotal();
    }

    public function updateKasbonField($field, $value)
    {
        $value = (float) $value;

        switch ($field) {
            case 'masuk':
                $this->gaji_data['kasbon_masuk'] = $value;
                break;
            case 'faktor':
                $this->gaji_data['kasbon_faktor'] = $value;
                break;
            case 'nominal_lembur':
                $this->gaji_data['kasbon_nominal'] = $value;
                break;
            case 'total':
                $this->gaji_data['kasbon'] = $value;
                break;
        }

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
        $subTotal = $this->gaji_data['gaji_setengah_bulan_nominal'] ?? 0;

        $subTotal += $this->gaji_data['lembur_senin_jumat_total'] ?? 0;
        $subTotal += $this->gaji_data['lembur_sabtu_total'] ?? 0;
        $subTotal += $this->gaji_data['lembur_minggu_total'] ?? 0;
        $subTotal += $this->gaji_data['lembur_hari_besar_total'] ?? 0;

        // Potongan dikurangi
        $subTotal -= $this->gaji_data['potongan_tidak_masuk_total'] ?? 0;
        $subTotal -= $this->gaji_data['potongan_tidak_disiplin_total'] ?? 0;

        // Item tambahan (manual dari user)
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
                case 'h':
                    $this->gaji_data['kasbon_masuk'] = $detail->masuk;
                    $this->gaji_data['kasbon_faktor'] = $detail->faktor;
                    $this->gaji_data['kasbon_nominal'] = $detail->nominal;
                    $this->gaji_data['kasbon'] = $detail->total;
                    break;
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

}
