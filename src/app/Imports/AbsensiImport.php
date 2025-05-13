<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AbsensiImport implements ToCollection, WithHeadingRow
{
    protected Collection $previewData;

    protected array $requiredHeaders = [
        'name',
        'tanggal',
        'masuk_pagi',
        'keluar_siang',
        'masuk_siang',
        'pulang_kerja',
        'masuk_lembur',
        'pulang_lembur',
    ];

    public function collection(Collection $rows)
    {
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

        $this->previewData = $rows;
    }

    public function getPreviewData(): array
    {
        return $this->previewData->map(function ($row) {
            return [
                'name' => $row['name'] ?? '',
                'tanggal' => $this->parseDate($row['tanggal'] ?? null),
                'masuk_pagi' => $this->parseTime($row['masuk_pagi'] ?? null),
                'keluar_siang' => $this->parseTime($row['keluar_siang'] ?? null),
                'masuk_siang' => $this->parseTime($row['masuk_siang'] ?? null),
                'pulang_kerja' => $this->parseTime($row['pulang_kerja'] ?? null),
                'masuk_lembur' => $this->parseTime($row['masuk_lembur'] ?? null),
                'pulang_lembur' => $this->parseTime($row['pulang_lembur'] ?? null),
            ];
        })->toArray();
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('d-m-Y');
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('H:i:s');
            }
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
