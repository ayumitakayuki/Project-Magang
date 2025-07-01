<?php

namespace App\Filament\Admin\Pages;

use App\Models\Karyawan;
use App\Services\GajiService;
use Filament\Pages\Page;
use Illuminate\Http\Request;
use Livewire\Component;

class SlipGaji extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Slip Gaji';
    protected static ?string $title = 'Slip Gaji';
    protected static string $view = 'filament.pages.slip-gaji';
    protected static ?int $navigationSort = 3;
    public $lokasi_options;
    public $proyek_options;
    public ?string $selected_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;
    public array $gaji_data = [];
    public $all_karyawan = null;
    public $additional_items = [];
    public $sub_total = 0;

    
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

    public static function getNavigationGroup(): ?string
    {
        return 'Penggajian';
    }

    public function mount(Request $request): void
    {
        $this->all_karyawan = Karyawan::get(['id_karyawan', 'nama']);
        
        $this->selected_id = $request->query('karyawan_id');
        
        $defaultStart = now()->subMonth()->startOfMonth();
        $defaultEnd = $defaultStart->copy()->endOfMonth();
        
        $this->start_date = $request->query('start_date') ?? $defaultStart->toDateString();
        $this->end_date = $request->query('end_date') ?? $defaultEnd->toDateString();

        if ($this->selected_id) {
            $this->hitungSlipGaji();
        }
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

    // private function calculateKasbonTotal()
    // {
    //     $masuk = $this->gaji_data['kasbon_masuk'] ?? 0;
    //     $faktor = $this->gaji_data['kasbon_faktor'] ?? 0;
    //     $nominal = $this->gaji_data['kasbon_nominal'] ?? 0;
        
    //     return $masuk * $nominal;
    // }

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
}