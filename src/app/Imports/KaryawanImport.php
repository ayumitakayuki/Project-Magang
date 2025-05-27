<?php

namespace App\Imports;

use App\Models\Karyawan;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KaryawanImport implements ToCollection, WithHeadingRow
{
    protected array $requiredHeaders = [
        'id_karyawan',
        'nama',
        'status',
        'lokasi',
        'jenis_proyek',
        'gaji_perbulan',
        'gaji_lembur_reguler',
        'gaji_lembur_sabtu',
        'gaji_lembur_minggu_haribesar',
        'gaji_harian',
    ];

    protected ?string $filename = null;

    public function __construct(?string $filename = null)
    {
        $this->filename = $filename;
    }

    public function collection(Collection $rows)
    {
        if ($this->filename !== 'template-karyawan.xlsx') {
            throw ValidationException::withMessages([
                'file' => 'Nama file tidak valid. Gunakan template-karyawan.xlsx.',
            ]);
        }

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'File kosong. Gunakan template resmi.',
            ]);
        }

        $headers = array_keys($rows->first()->toArray());
        $missing = array_diff($this->requiredHeaders, $headers);

        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'file' => 'Format tidak sesuai. Kolom wajib: ' . implode(', ', $this->requiredHeaders),
            ]);
        }

        foreach ($rows as $row) {
            if (empty($row['id_karyawan']) || empty($row['nama'])) {
                continue;
            }

            Karyawan::updateOrInsert(
                ['id_karyawan' => $row['id_karyawan']],
                [
                    'nama' => $row['nama'],
                    'status' => $row['status'] ?? null,
                    'lokasi' => $row['lokasi'] ?? null,
                    'jenis_proyek' => $row['jenis_proyek'] ?? null,
                    'gaji_perbulan' => $row['gaji_perbulan'] ?? null,
                    'gaji_lembur_reguler' => $row['gaji_lembur_reguler'] ?? null,
                    'gaji_lembur_sabtu' => $row['gaji_lembur_sabtu'] ?? null,
                    'gaji_lembur_minggu_haribesar' => $row['gaji_lembur_minggu_haribesar'] ?? null,
                    'gaji_harian' => $row['gaji_harian'] ?? null,
                ]
            );
        }
    }
}
