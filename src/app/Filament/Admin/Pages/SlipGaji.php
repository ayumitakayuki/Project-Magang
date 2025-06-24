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
    
    public $newItem = [
        'type' => '',
        'masuk' => '',
        'faktor' => '',
        'nominal_lembur' => '',
        'total' => ''
    ];

    protected $rules = [
        'newItem.type' => 'required',
        'newItem.masuk' => 'required|numeric|min:0',
        'newItem.faktor' => 'required|numeric|min:0',
        'newItem.nominal_lembur' => 'required|numeric|min:0',
        'newItem.total' => 'required|numeric|min:0'
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
        }
    }

    public function addItem()
    {
        $this->validate([
            'newItem.type' => 'required',
            'newItem.masuk' => 'required|numeric|min:0',
            'newItem.faktor' => 'required|numeric|min:0',
            'newItem.nominal_lembur' => 'required|numeric|min:0',
        ]);
        $this->newItem['total'] = 
            (float)$this->newItem['masuk'] * 
            (float)$this->newItem['faktor'] * 
            (float)$this->newItem['nominal_lembur'];

        $this->validate([
            'newItem.total' => 'required|numeric|min:0'
        ]);

        $itemTypes = [
            'uang_makan_lembur_malam' => [
                'keterangan' => 'Uang Makan Lembur Malam',
                'no' => 'i'
            ],
            'uang_makan_lembur_jalan' => [
                'keterangan' => 'Uang Makan Lembur Jalan',
                'no' => 'j'
            ],
            'bpjs_kesehatan' => [
                'keterangan' => 'Potongan BPJS Kesehatan',
                'no' => 'k'
            ],
            'bpjs_tk' => [
                'keterangan' => 'Potongan BPJS TK',
                'no' => 'l'
            ],
            'bpjs_gabungan' => [
                'keterangan' => 'Potongan BPJS Kesehatan + TK',
                'no' => 'm'
            ]
        ];

        $type = $this->newItem['type'];
        if (empty($type)) {
            session()->flash('error', 'Silakan pilih jenis item');
            return;
        }

        $keterangan = $itemTypes[$type]['keterangan'];
        
        // Check if item already exists
        if (collect($this->additional_items)->contains('keterangan', $keterangan)) {
            session()->flash('error', 'Item ' . $keterangan . ' sudah ditambahkan');
            return;
        }

        // Calculate total
        $total = $this->newItem['masuk'] * $this->newItem['faktor'] * $this->newItem['nominal_lembur'];

        // Add new item
        $this->additional_items[] = [
            'no' => $itemTypes[$type]['no'],
            'keterangan' => $keterangan,
            'masuk' => $this->newItem['masuk'],
            'tidak_masuk' => '-',
            'faktor' => $this->newItem['faktor'],
            'jumlah_hari' => $this->newItem['total'],
            'nominal_lembur' => $this->newItem['nominal_lembur'],
            'total' => $total
        ];

        // Reset form
        $this->newItem = [
            'type' => '',
            'masuk' => '',
            'faktor' => '',
            'nominal_lembur' => '',
            'total' => ''
        ];

        $this->hitungSlipGaji();
    }

    public function deleteItem($index)
    {
        unset($this->additional_items[$index]);
        $this->additional_items = array_values($this->additional_items);
        $this->hitungSlipGaji();
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
        }

        // Recalculate kasbon total
        $this->gaji_data['kasbon'] = $this->calculateKasbonTotal();
        $this->calculateGrandTotal();
    }

    private function calculateKasbonTotal()
    {
        $masuk = $this->gaji_data['kasbon_masuk'] ?? 0;
        $faktor = $this->gaji_data['kasbon_faktor'] ?? 0;
        $nominal = $this->gaji_data['kasbon_nominal'] ?? 0;
        
        return $masuk * $faktor * $nominal;
    }

    private function calculateGrandTotal()
    {
        $subTotal = $this->calculateSubTotal();
        $kasbon = $this->gaji_data['kasbon'] ?? 0;
        $this->gaji_data['total_gaji'] = $subTotal - $kasbon;
    }

    private function calculateSubTotal()
    {
        $subTotal = $this->gaji_data['gaji_pokok'] ?? 0;
        
        // Add all additional items
        foreach ($this->additional_items as $item) {
            if (str_contains(strtolower($item['keterangan']), 'potongan')) {
                $subTotal -= $item['total'];
            } else {
                $subTotal += $item['total'];
            }
        }
        
        return $subTotal;
    }
}