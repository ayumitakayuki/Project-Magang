<x-filament::page>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-filament::section>
            <x-slot name="heading">Kasbon Loan</x-slot>
            <x-slot name="description">Buat & kelola pinjaman kasbon (pokok, tenor, cicilan).</x-slot>

            <div class="mt-4">
                <x-filament::button tag="a" :href="\App\Filament\Admin\Resources\KasbonLoanResource::getUrl('index')">
                    Buka Kasbon Loan
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Kasbon Payment</x-slot>
            <x-slot name="description">Catatan pembayaran/ pemotongan kasbon.</x-slot>

            <div class="mt-4">
                <x-filament::button tag="a" :href="\App\Filament\Admin\Resources\KasbonPaymentResource::getUrl('index')">
                    Buka Kasbon Payment
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Laporan Kasbon</x-slot>
            <x-slot name="description">Rekap sisa bulan lalu, potong 1–15 / 16–akhir, sisa bulan ini.</x-slot>

            <div class="mt-4">
                <x-filament::button
                    tag="a"
                    :href="\App\Filament\Admin\Pages\LaporanKasbon::getUrl()">
                    Buka Laporan
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament::page>


