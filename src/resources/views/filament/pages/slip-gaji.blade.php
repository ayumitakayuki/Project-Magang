<x-filament::page>
    <x-filament::card class="bg-blue-100 rounded-xl p-6">
        <form method="GET" class="space-y-6">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Pilih Karyawan</label>
                    <select name="karyawan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Pilih Karyawan...</option>
                        @foreach($all_karyawan as $karyawan)
                            <option value="{{ $karyawan->id_karyawan }}" {{ $selected_id == $karyawan->id_karyawan ? 'selected' : '' }}>
                                {{ $karyawan->id_karyawan }} - {{ $karyawan->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Periode Awal</label>
                    <input type="text" name="start_date" id="start_date" value="{{ $start_date }}" 
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Periode Akhir</label>
                    <input type="text" name="end_date" id="end_date" value="{{ $end_date }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Hitung Gaji
                    </button>
                </div>
            </div>
        </form>

         <!-- Add error message alert here -->
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if(!empty($gaji_data))
            <!-- ...existing gaji data display code... -->
        @endif

        @if(!empty($gaji_data))
            <div class="mt-8 bg-white p-6 rounded-lg border">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold">SLIP GAJI KARYAWAN</h2>
                    <p class="text-gray-600">Periode: {{ \Carbon\Carbon::parse($gaji_data['periode_awal'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($gaji_data['periode_akhir'])->format('d M Y') }}</p>
                </div>

                <div class="flex flex-wrap mb-4">
                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm w-full">
                        <div class="grid grid-cols-3 gap-4">
                            <!-- Employee ID & Name -->
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-600">ID Karyawan:</span>
                                    <span class="text-sm">{{ $gaji_data['id_karyawan'] }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-600">Nama:</span>
                                    <span class="text-sm">{{ $gaji_data['nama'] }}</span>
                                </div>
                            </div>

                            <!-- Status & Location -->
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-600">Status:</span>
                                    <span class="text-sm">{{ ucwords($gaji_data['status']) }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-600">Lokasi:</span>
                                    <span class="text-sm">{{ ucwords($gaji_data['lokasi']) }}</span>
                                </div>
                            </div>

                            <!-- Project -->
                            <div class="space-y-2">
                                @if($gaji_data['jenis_proyek'])
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-600">Proyek:</span>
                                        <span class="text-sm">{{ $gaji_data['jenis_proyek'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <table class="custom-table">
                    <!-- Table Header -->
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Masuk</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Faktor</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal Lembur</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fixed Items -->
                        <tr>
                            <td class="px-6 py-4 text-center">a</td>
                            <td class="px-6 py-4">Gaji Setengah bln</td>
                            <td class="px-6 py-4 text-center">{{ $gaji_data['total_hari_kerja'] }}</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">1</td>
                            <td class="px-6 py-4 text-center">{{ $gaji_data['total_hari_kerja'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">b</td>
                            <td class="px-6 py-4">Lembur senin s/d jumat</td>
                            <td class="px-6 py-4 text-center">{{ $gaji_data['total_hari_lembur'] }}</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">1.5</td>
                            <td class="px-6 py-4 text-center">{{ $gaji_data['total_hari_lembur'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">c</td>
                            <td class="px-6 py-4">Lembur Sabtu</td>
                            <td class="px-6 py-4 text-center">x</td>
                            <td class="px-6 py-4 text-center">x</td>
                            <td class="px-6 py-4 text-center">1.5</td>
                            <td class="px-6 py-4 text-center">-</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">d</td>
                            <td class="px-6 py-4">Lembur Minggu</td>
                            <td class="px-6 py-4 text-center">x</td>
                            <td class="px-6 py-4 text-center">x</td>
                            <td class="px-6 py-4 text-center">2</td>
                            <td class="px-6 py-4 text-center">-</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">e</td>
                            <td class="px-6 py-4">Lembur Hari Besar</td>
                            <td class="px-6 py-4 text-center">x</td>
                            <td class="px-6 py-4 text-center">x</td>
                            <td class="px-6 py-4 text-center">2</td>
                            <td class="px-6 py-4 text-center">-</td>
                        </tr>
                        @foreach($additional_items as $index => $item)
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $item['no'] }}</td>
                            <td class="px-6 py-4 flex justify-between items-center">
                                {{ $item['keterangan'] }}
                                <button wire:click="deleteItem({{ $index }})" 
                                        class="text-red-600 hover:text-red-800 focus:outline-none ml-2"
                                        title="Hapus item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center">{{ $item['masuk'] }}</td>
                            <td class="px-6 py-4 text-center">{{ $item['tidak_masuk'] }}</td>
                            <td class="px-6 py-4 text-center">{{ $item['faktor'] }}</td>
                            <td class="px-6 py-4 text-center">{{ $item['jumlah_hari'] }}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td class="px-6 py-4 text-center">f</td>
                            <td class="px-6 py-4">Potongan Gaji Tdk Masuk (Perjam)</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">-</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">g</td>
                            <td class="px-6 py-4">Potongan Gaji Tdk Disiplin</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-center">-</td>
                        </tr>
                        
                        <tr class="font-bold border-t-2 border-gray-200">
                            <td colspan="5" class="px-6 py-4 text-right">JML</td>
                            <td class="px-6 py-4 text-right">Rp {{ number_format($gaji_data['total_gaji'], 0, ',', '.') }}</td>
                        </tr>
                        {{-- kasbon --}}
                        <tr x-data="{
                            editing: {
                                masuk: false,
                                faktor: false,
                                nominal: false
                            },
                            values: {
                                masuk: '{{ $gaji_data['kasbon_masuk'] ?? 0 }}',
                                faktor: '{{ $gaji_data['kasbon_faktor'] ?? 0 }}',
                                nominal: '{{ $gaji_data['kasbon_nominal'] ?? 0 }}'
                            }
                        }">
                            <td class="px-4 py-2 text-center">h</td>
                            <td class="px-4 py-2">Kasbon</td>
                            <td class="px-4 py-2 text-center">
                                <div x-show="!editing.masuk" 
                                    @click="editing.masuk = true" 
                                    class="cursor-pointer hover:bg-gray-100 px-2 py-1 rounded inline-flex items-center gap-1">
                                    {{ $gaji_data['kasbon_masuk'] ?? '-' }}
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </div>
                                <input type="number" 
                                    x-show="editing.masuk" 
                                    x-model="values.masuk"
                                    @blur="editing.masuk = false; $wire.updateKasbonField('masuk', values.masuk)"
                                    @keydown.enter="editing.masuk = false; $wire.updateKasbonField('masuk', values.masuk)"
                                    class="w-16 text-sm border-gray-300 rounded-md">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <div x-show="!editing.faktor" 
                                    @click="editing.faktor = true" 
                                    class="cursor-pointer hover:bg-gray-100 px-2 py-1 rounded inline-flex items-center gap-1">
                                    {{ $gaji_data['kasbon_faktor'] ?? '1' }}
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </div>
                                
                                <input type="number" 
                                    x-show="editing.faktor" 
                                    x-model="values.faktor" 
                                    step="0.1" 
                                    @blur="editing.faktor = false; $wire.updateKasbonField('faktor', values.faktor)" 
                                    @keydown.enter="editing.faktor = false; $wire.updateKasbonField('faktor', values.faktor)" 
                                    class="w-16 text-sm border-gray-300 rounded-md">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <div x-show="!editing.nominal" 
                                    @click="editing.nominal = true" 
                                    class="cursor-pointer hover:bg-gray-100 px-2 py-1 rounded inline-flex items-center gap-1">
                                    Rp {{ number_format($gaji_data['kasbon_nominal'] ?? 0, 0, ',', '.') }}
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </div>
                                <input type="number" 
                                    x-show="editing.nominal" 
                                    x-model="values.nominal"
                                    @blur="editing.nominal = false; $wire.updateKasbonField('nominal', values.nominal)"
                                    @keydown.enter="editing.nominal = false; $wire.updateKasbonField('nominal', values.nominal)"
                                    class="w-16 text-sm border-gray-300 rounded-md">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <div x-show="!editing.kasbonTotal" 
                                    @click="editing.kasbonTotal = true" 
                                    class="cursor-pointer hover:bg-gray-100 px-2 py-1 rounded inline-flex items-center gap-1">
                                    {{ isset($gaji_data['kasbon']) && isset($gaji_data['kasbon_faktor']) 
                                        ? number_format($gaji_data['kasbon'] * $gaji_data['kasbon_faktor'], 0, ',', '.') 
                                        : '0' 
                                    }}
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </div>
                                
                                <input type="number" 
                                    x-show="editing.kasbonTotal" 
                                    x-model="values.kasbonTotal" 
                                    @blur="editing.kasbonTotal = false; $wire.updateKasbonField('total', values.kasbonTotal)" 
                                    @keydown.enter="editing.kasbonTotal = false; $wire.updateKasbonField('total', values.kasbonTotal)" 
                                    class="w-20 text-sm border-gray-300 rounded-md">
                            </td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="5" class="px-6 py-4 text-right">Grand Total</td>
                            <td class="px-6 py-4 text-right">Rp {{ number_format($gaji_data['total_gaji'], 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Add Item Form -->
                <div x-data="{ 
                    showForm: false,
                    calculateTotal() {
                        const masuk = parseFloat(this.formData.masuk) || 0;
                        const faktor = parseFloat(this.formData.faktor) || 0;
                        const nominal = parseFloat(this.formData.nominal_lembur) || 0;
                        this.formData.total = masuk * faktor * nominal;
                    },
                    formData: {
                        type: '',
                        masuk: '',
                        faktor: '',
                        nominal_lembur: '',
                        total: ''
                    }
                }" class="mt-4">
                    <button @click="showForm = !showForm" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-150 ease-in-out">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span class="text-gray-900 font-medium">Tambah Item</span>
                    </button>

                    <div x-show="showForm"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-90"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-300"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-90"
                        class="mt-4 p-4 border border-gray-200 rounded-lg bg-white shadow-sm">
                        <form wire:submit.prevent="addItem">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Item</label>
                                    <select x-model="formData.type" 
                                            wire:model="newItem.type" 
                                            class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">Pilih Item...</option>
                                        <optgroup label="Uang Makan">
                                            <option value="uang_makan_lembur_malam">Uang Makan Lembur Malam</option>
                                            <option value="uang_makan_lembur_jalan">Uang Makan Lembur Jalan</option>
                                        </optgroup>
                                        <optgroup label="Potongan">
                                            <option value="bpjs_kesehatan">Potongan BPJS Kesehatan</option>
                                            <option value="bpjs_tk">Potongan BPJS TK</option>
                                            <option value="bpjs_gabungan">Potongan BPJS Kesehatan + TK</option>
                                        </optgroup>
                                    </select>
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Masuk</label>
                                    <input type="number" 
                                        x-model="formData.masuk"
                                        wire:model="newItem.masuk"
                                        @input="calculateTotal"
                                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                                        placeholder="0">
                                </div>

                                <div>
                                    <label class="text-sm font-medium text-gray-700">Faktor</label>
                                    <input type="number" 
                                        x-model="formData.faktor"
                                        wire:model="newItem.faktor"
                                        @input="calculateTotal"
                                        step="0.1"
                                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                                        placeholder="0">
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Nominal</label>
                                    <input type="number" 
                                        x-model="formData.nominal_lembur"
                                        wire:model="newItem.nominal_lembur"
                                        @input="calculateTotal"
                                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                                        placeholder="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Total</label>
                                    <input type="number" 
                                        x-model="formData.total"
                                        wire:model="newItem.total"
                                        class="w-full rounded-md border-gray-300 shadow-sm bg-gray-50"
                                        readonly>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end space-x-2">
                                <button type="button" 
                                        @click="showForm = false"
                                        class="px-3 py-1 text-sm border border-gray-300 rounded-md">
                                    Batal
                                </button>
                                <button type="submit" 
                                        class="px-3 py-1 text-sm border border-gray-300 rounded-md">
                                    Tambah
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::card>
</x-filament::page>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/airbnb.css">
    <style>
        .custom-table {
            border-collapse: collapse;
            width: 100%;
            margin: 0 auto;
            background-color: #ffffff;
            font-size: 0.875rem;
        }

        .custom-table th,
        .custom-table td {
            border: 1px solid black;
            padding: 8px 12px;
            text-align: left;
            vertical-align: middle;
        }

        .custom-table th {
            background-color: #f3f4f6;
            font-weight: 600;
        }

        .custom-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .custom-table tr:hover {
            background-color: #f1f5f9;
        }

        .bg-yellow-100 {
            background-color: #fef9c3;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .filament-main-content {
                position: absolute;
                left: 0;
                top: 0;
            }
            .filament-main-content * {
                visibility: visible;
            }
            button {
                display: none !important;
            }
            .custom-table {
                font-size: 0.8rem;
                background: #ffffff;
            }
            .custom-table tr:nth-child(even),
            .custom-table tr:hover {
                background: #ffffff;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            flatpickr("#start_date", {
                dateFormat: "Y-m-d",
                defaultDate: "{{ $start_date }}"
            });
            
            flatpickr("#end_date", {
                dateFormat: "Y-m-d", 
                defaultDate: "{{ $end_date }}"
            });
        });
    </script>
@endpush