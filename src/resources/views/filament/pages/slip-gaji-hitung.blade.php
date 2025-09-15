<x-filament::page>
    <x-filament::card class="bg-blue-100 rounded-xl p-6">
        <form wire:submit.prevent="hitungGaji" class="space-y-6">
            <input type="hidden" name="karyawan_id" value="{{ $karyawan_id }}">
            @if ($editingGajiId)
                <div class="mb-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded">
                    ⚠️ <strong>Mode Edit:</strong> Anda sedang mengedit slip gaji yang sudah pernah disimpan.
                </div>
            @endif
            <div class="flex flex-wrap gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Periode Awal</label>
                <input
                    type="text"
                    id="start_date"
                    wire:model.defer="start_date"
                    placeholder="{{ now()->toDateString() }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-black">
            </div>

            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Periode Akhir</label>
                <input
                    type="text"
                    id="end_date"
                    wire:model.defer="end_date"
                    placeholder="{{ now()->toDateString() }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-black">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Tipe Pembayaran</label>
                <select
                    wire:model="tipe_pembayaran"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-black"
                >
                    <option value="payroll">Payroll</option>
                    <option value="non-payroll">Non Payroll</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit"
                    class="bg-blue-500 text-black px-6 py-2 rounded-md shadow hover:bg-blue-600 transition-all duration-200 ease-in-out">
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

        @if (session()->has('success'))
            <div 
                x-data="{ show: true }" 
                x-init="setTimeout(() => show = false, 3000)" 
                x-show="show"
                x-transition
                class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded mb-4 shadow"
                role="alert"
            >
                <strong>Berhasil!</strong> {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div 
                x-data="{ show: true }" 
                x-init="setTimeout(() => show = false, 4000)" 
                x-show="show"
                x-transition
                class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-4 shadow"
                role="alert"
            >
                <strong>Gagal!</strong> {{ session('error') }}
            </div>
        @endif

        @if(!empty($gaji_data))
            <div class="mt-8 bg-white p-6 rounded-lg border">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold">SLIP GAJI KARYAWAN</h2>
                    <p class="text-gray-600">Periode: {{ \Carbon\Carbon::parse($gaji_data['periode_awal'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($gaji_data['periode_akhir'])->format('d M Y') }}</p>
                </div>

                <div class="w-full bg-white border border-gray-200 rounded-xl shadow px-6 py-4">
                    <div class="flex flex-wrap sm:flex-nowrap items-center justify-start text-sm text-gray-800 divide-x divide-gray-300">

                        <div class="px-4 first:pl-0">
                            <span class="font-semibold text-gray-600">ID Karyawan:</span>
                            <span>{{ $gaji_data['id_karyawan'] }}</span>
                        </div>

                        <div class="px-4">
                            <span class="font-semibold text-gray-600">Nama:</span>
                            <span>{{ $gaji_data['nama'] }}</span>
                        </div>

                        <div class="px-4">
                            <span class="font-semibold text-gray-600">Status:</span>
                            <span>{{ ucwords($gaji_data['status']) }}</span>
                        </div>

                        <div class="px-4">
                            <span class="font-semibold text-gray-600">Lokasi:</span>
                            <span>{{ ucwords($gaji_data['lokasi']) }}</span>
                        </div>

                        @if($gaji_data['jenis_proyek'])
                            <div class="px-4">
                                <span class="font-semibold text-gray-600">Proyek:</span>
                                <span>{{ $gaji_data['jenis_proyek'] }}</span>
                            </div>
                        @endif

                    </div>
                </div>
                @php
                    // Format desimal Indonesia: koma, tanpa ribuan untuk kolom Masuk/Faktor
                    $fmtId = function($n, $max=2) {
                        $n = (float) ($n ?? 0);
                        if (fmod($n, 1.0) === 0.0) return (int) $n; // 10
                        $s = number_format($n, $max, ',', '');      // 10,50
                        return rtrim(rtrim($s, '0'), ',');          // 10,5
                    };
                @endphp

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
                        @php $rowIndex = 0; $labels = range('a', 'z'); @endphp
                        @php
                            $status = strtolower($gaji_data['status'] ?? '');
                            $isHarianLepas = $status === 'harian lepas';
                            $labelGajiPokok = $isHarianLepas ? 'Gaji Harian' : 'Gaji Setengah bln';
                            $gajiHarianMasuk = $isHarianLepas
                                ? (is_numeric($gaji_data['gaji_harian_masuk'] ?? null) ? (int)$gaji_data['gaji_harian_masuk'] : 0)
                                : 0;
                            $gajiHarianNominal = $isHarianLepas
                                ? ($gaji_data['gaji_harian_nominal'] ?? 0)
                                : 0;
                            $gajiHarianTotal = $isHarianLepas
                                ? ($gajiHarianMasuk * $gajiHarianNominal)
                                : ($gaji_data['gaji_setengah_bulan_nominal'] ?? 0);
                        @endphp

                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td class="px-6 py-4">{{ $labelGajiPokok }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($isHarianLepas)
                                    {{ $gajiHarianMasuk }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-right">
                                {{ number_format($isHarianLepas ? $gajiHarianNominal : ($gaji_data['gaji_setengah_bulan_nominal'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{ number_format($gajiHarianTotal, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td>Lembur senin s/d jumat</td>
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_senin_jumat_masuk'] ?? 0, 1) }}</td>
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_senin_jumat_faktor'] ?? 0, 2) }}</td>
                            <td class="text-right">
                                {{ number_format((float) ($gaji_data['lembur_senin_jumat_nominal'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="text-right">{{ number_format($gaji_data['lembur_senin_jumat_total'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td class="px-6 py-4">Lembur Sabtu</td>                  
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_sabtu_masuk'] ?? 0, 1) }}</td>
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_sabtu_faktor'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($gaji_data['lembur_sabtu_nominal'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($gaji_data['lembur_sabtu_total'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td class="px-6 py-4">Lembur Minggu</td>
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_minggu_masuk'] ?? 0, 1) }}</td>
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_minggu_faktor'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['lembur_minggu_nominal'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['lembur_minggu_total'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td class="px-6 py-4">Lembur Hari Besar</td>
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_hari_besar_masuk'] ?? 0, 1) }}</td>
                            <td class="text-center">{{ $fmtId($gaji_data['lembur_hari_besar_faktor'] ?? 0, 2) }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['lembur_hari_besar_nominal'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['lembur_hari_besar_total'], 0, ',', '.') }}</td>
                        </tr>
                        @foreach($additional_items as $index => $item)
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
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
                            <td class="px-6 py-4 text-center">
                                {{ fmod($item['masuk'], 1) === 0.0 ? (int) $item['masuk'] : number_format($item['masuk'], 1, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{ fmod($item['faktor'], 1) === 0.0
                                    ? (int) $item['faktor']
                                    : rtrim(rtrim(number_format($item['faktor'], 2, '.', ''), '0'), '.') }}
                            </td>

                            <td class="px-6 py-4 text-right">{{ number_format($item['nominal_lembur'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">
                                {{ fmod($item['total'], 1) === 0.0 ? (int) $item['total'] : number_format($item['total'], 1, ',', '.') }}
                            </td>

                        </tr>
                        @endforeach
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td class="px-6 py-4">Potongan Gaji Tdk Masuk (Perjam)</td>
                            <td class="px-6 py-4 text-center">
                                {{ fmod($gaji_data['potongan_tidak_masuk_masuk'], 1) === 0.0 
                                    ? (int) $gaji_data['potongan_tidak_masuk_masuk'] 
                                    : number_format($gaji_data['potongan_tidak_masuk_masuk'], 1, '.', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['potongan_tidak_masuk_nominal'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['potongan_tidak_masuk_total'], 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td class="px-6 py-4">Potongan Gaji Tdk Disiplin</td>
                            <td class="px-6 py-4 text-center">
                                {{ fmod($gaji_data['potongan_tidak_disiplin_masuk'], 1) === 0.0 
                                    ? (int) $gaji_data['potongan_tidak_disiplin_masuk'] 
                                    : number_format($gaji_data['potongan_tidak_disiplin_masuk'], 1, '.', '.') }}
                            </td>
                            <td class="px-6 py-4 text-center">-</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['potongan_tidak_disiplin_nominal'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($gaji_data['potongan_tidak_disiplin_total'], 0, ',', '.') }}</td>
                        </tr>
                        <tr class="font-bold border-t-2 border-gray-200">
                            <td colspan="5" class="px-6 py-4 text-right">JML</td>
                            <td class="px-6 py-4 text-right">
                                Rp {{ number_format($sub_total, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- kasbon --}}
                        <tr>
                            <td class="px-6 py-4 text-center">{{ $labels[$rowIndex++] }}</td>
                            <td class="px-4 py-2">Kasbon (otomatis)</td>
                            <td class="px-4 py-2 text-center">{{ (int)($gaji_data['kasbon_masuk'] ?? 0) }}</td>
                            <td class="px-4 py-2 text-center">-</td>
                            <td class="px-4 py-2 text-right">
                                Rp {{ number_format((float)($gaji_data['kasbon_nominal'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-right font-semibold">
                                Rp {{ number_format((float)($gaji_data['kasbon'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="font-bold">
                            <td colspan="5" class="px-6 py-4 text-right">Grand Total</td>
                            <td class="px-6 py-4 text-right">
                                Rp {{
                                    number_format(
                                        (empty($gaji_data['kasbon']) || $gaji_data['kasbon'] == 0)
                                            ? $sub_total
                                            : ($sub_total - $gaji_data['kasbon']),
                                        0, ',', '.'
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-6 flex justify-end gap-2">
                    <form wire:submit.prevent="simpanSlipGaji">
                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 border text-sm font-medium text-gray-700 border-gray-300 rounded-md hover:bg-gray-100 transition">
                            Simpan ke Database
                        </button>
                    </form>

                    @if ($editingGajiId)
                        <a href="{{ route('filament.admin.pages.histori-slip-gaji') }}"
                            class="inline-flex items-center px-6 py-3 border text-sm font-medium text-gray-700 border-gray-300 rounded-md hover:bg-gray-100 transition">
                            Batal Edit
                        </a>
                    @endif
                </div>
                @stack('scripts')

                <!-- Add Item Form -->
                <div x-data="{
                    showForm: false,
                    karyawanNominals: @js($gaji_data['nominals'] ?? []),

                    // helper: dukung koma sebagai desimal
                    toFloat(v){ return parseFloat(String(v ?? '').replace(',', '.')) || 0 },
                    normalize(v){ return String(v ?? '').replace(',', '.') },

                    calculateTotal() {
                        const masuk   = this.toFloat(this.formData.masuk);
                        const faktor  = this.toFloat(this.formData.faktor) || 1;
                        const nominal = this.toFloat(this.formData.nominal_lembur);
                        this.formData.total = masuk * faktor * nominal;

                        // sinkron ke Livewire (pakai titik biar lolos numeric)
                        $wire.newItem.masuk          = this.normalize(this.formData.masuk);
                        $wire.newItem.faktor         = this.normalize(this.formData.faktor);
                        $wire.newItem.nominal_lembur = this.normalize(this.formData.nominal_lembur);
                        $wire.newItem.total          = this.formData.total;
                    },

                    formData: { type:'', masuk:'', faktor:'', nominal_lembur:'', total:'' }
                }" class="mt-4">

                    <button @click="showForm = !showForm"
                        <button @click="showForm = !showForm"
                        class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md 
                            hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 
                            transition-all duration-150 ease-in-out text-sm text-gray-900 font-medium">
                        Tambah Item
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
                                    <div class="relative overflow-visible z-50">
                                        <select
                                            x-model="formData.type"
                                            @change="
                                                const isPerizinan = ['perizinan_sakit','perizinan_berduka','perizinan_tanpa_alasan'].includes(formData.type);

                                                // perizinan: nominal 0, faktor default 1 (boleh diubah user)
                                                formData.nominal_lembur = isPerizinan ? 0 : (karyawanNominals[formData.type] || 0);
                                                if (isPerizinan && (!formData.faktor || this.toFloat(formData.faktor) <= 0)) {
                                                    formData.faktor = '1';
                                                }

                                                $wire.newItem.type           = formData.type;
                                                $wire.newItem.nominal_lembur = this.normalize(formData.nominal_lembur); // koma -> titik
                                                $wire.newItem.faktor         = this.normalize(formData.faktor);         // koma -> titik

                                                this.calculateTotal();
                                                "

                                            wire:model="newItem.type"
                                            class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
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

                                            <!-- Perizinan -->
                                            <optgroup label="Perizinan">
                                                <option value="perizinan_sakit">Perizinan Sakit (Surat Dokter)</option>
                                                <option value="perizinan_berduka">Perizinan Berduka</option>
                                                <option value="perizinan_tanpa_alasan">Potongan Perizinan Tanpa Alasan</option>
                                            </optgroup>
                                            </select>

                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Masuk</label>
                                    <input type="number" 
                                        x-model="formData.masuk"
                                        wire:model.defer="newItem.masuk"
                                        @input="
                                            $wire.newItem.masuk = formData.masuk;
                                            calculateTotal();
                                        "
                                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                                        placeholder="0">
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Faktor</label>
                                    <input type="number" 
                                        x-model="formData.faktor"
                                        wire:model.defer="newItem.faktor"
                                        @input="
                                            $wire.newItem.faktor = formData.faktor;
                                            calculateTotal();
                                        "
                                        step="0.1"
                                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                                        placeholder="1">

                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Nominal</label>
                                    <input type="number" 
                                        x-model="formData.nominal_lembur"
                                        wire:model.defer="newItem.nominal_lembur"
                                        @input="
                                            $wire.newItem.nominal_lembur = formData.nominal_lembur;
                                            calculateTotal();
                                        "
                                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm text-sm"
                                        placeholder="0">

                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Total</label>
                                    <input type="number" 
                                        x-model="formData.total"
                                        wire:model.defer="newItem.total"
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
        .col-span-3 {
            overflow: visible !important;
            position: relative;
            z-index: 50;
        }

        select {
            background-color: white;
            color: black;
        }
    </style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  window.addEventListener("load", function () {
    setTimeout(function () {
      flatpickr("#start_date", { dateFormat: "Y-m-d", defaultDate: "{{ $start_date ?? '' }}" });
      flatpickr("#end_date",   { dateFormat: "Y-m-d", defaultDate: "{{ $end_date ?? '' }}" });
    }, 100);
  });

  // === Auto-refresh Slip ketika Rekap disimpan ===
  window.addEventListener('rekap-saved', (e) => {
    const d = e.detail || {};
    if (String(@json($karyawan_id)) === String(d.karyawanId ?? d.karyawanKode)
        && @json($start_date) === d.start
        && @json($end_date)   === d.end) {
        @this.call('hitungGaji');
    }
    });
</script>
@endpush


