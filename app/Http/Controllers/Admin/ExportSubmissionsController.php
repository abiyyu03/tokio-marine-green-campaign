<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SubmissionExport;
use DateTimeInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor peserta ke CSV, memakai filter yang sama persis dengan halaman daftar.
 *
 * Sengaja rute GET biasa, bukan aksi Livewire: unduhan lewat Livewire ditampung
 * penuh di memori lalu di-base64 ke dalam payload JSON — di shared hosting,
 * beberapa ribu peserta cukup untuk menghabiskan memory_limit.
 *
 * Kolom dan barisnya datang dari App\Support\SubmissionExport, sama dengan
 * ekspor Excel. Lihat ExportSubmissionsXlsxController.
 */
class ExportSubmissionsController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $export = SubmissionExport::fromRequest($request);
        $export->logAudit($request, 'csv');

        $columns = $export->columns();

        return response()->streamDownload(function () use ($export, $columns) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8: tanpa ini Excel di Windows membaca nama berkarakter
            // non-ASCII dan "CO₂" sebagai karakter rusak.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_column($columns, 'label'));

            foreach ($export->rows() as $row) {
                fputcsv($handle, $this->format($row, $columns));
            }

            fclose($handle);
        }, $export->filename('csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * CSV tidak punya tipe: semuanya jadi teks di sini.
     *
     * @param  list<mixed>  $row
     * @param  list<array{label: string, type: string, width: float}>  $columns
     * @return list<string>
     */
    private function format(array $row, array $columns): array
    {
        $out = [];

        foreach ($row as $index => $value) {
            $type = $columns[$index]['type'] ?? SubmissionExport::TEXT;

            $out[] = $this->safe(match (true) {
                $value instanceof DateTimeInterface => $value->format(
                    $type === SubmissionExport::DATE ? 'Y-m-d' : 'Y-m-d H:i'
                ),
                default => $value,
            });
        }

        return $out;
    }

    /**
     * Sel yang diawali = + - @ tab atau CR dieksekusi Excel dan Google Sheets
     * sebagai rumus. Nama dan nomor WhatsApp datang dari formulir publik, jadi
     * nama seperti =HYPERLINK("https://…") akan berjalan begitu berkasnya
     * dibuka. Diawali petik tunggal supaya dibaca sebagai teks.
     */
    private function safe(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r]/', $text) === 1 ? "'".$text : $text;
    }
}
