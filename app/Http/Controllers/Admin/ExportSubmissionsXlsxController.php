<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SubmissionExport;
use DateTimeInterface;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor peserta ke Excel (.xlsx).
 *
 * Kolom dan barisnya identik dengan ekspor CSV — keduanya membaca
 * App\Support\SubmissionExport. Bedanya cuma cara menulis: di sini angka
 * ditulis sebagai angka dan tanggal sebagai tanggal, sehingga bisa langsung
 * disortir, dijumlah, dan dibuat pivot tanpa "Text to Columns" lebih dulu.
 * Itu satu-satunya alasan format ini ada di samping CSV.
 *
 * openspout menulis sambil jalan (baris demi baris ke berkas sementara, lalu
 * di-zip saat close), jadi memorinya datar seperti CSV. PhpSpreadsheet tidak:
 * ia menyusun seluruh workbook di memori lebih dulu.
 */
class ExportSubmissionsXlsxController extends Controller
{
    private const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __invoke(Request $request): StreamedResponse
    {
        $export = SubmissionExport::fromRequest($request);
        $export->logAudit($request, 'xlsx');

        $columns = $export->columns();

        return response()->streamDownload(function () use ($export, $columns) {
            $this->write($export, $columns);
        }, $export->filename('xlsx'), [
            'Content-Type' => self::CONTENT_TYPE,
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * @param  list<array{label: string, type: string, width: float}>  $columns
     */
    private function write(SubmissionExport $export, array $columns): void
    {
        $options = new Options;

        // Berkas sementara diarahkan ke storage/, bukan /tmp: di sebagian
        // shared hosting /tmp terkunci open_basedir dan zip-nya gagal senyap.
        $options->setTempFolder(storage_path('framework/cache'));

        foreach ($columns as $index => $column) {
            $options->setColumnWidth($column['width'], $index + 1);
        }

        $writer = new Writer($options);
        $writer->openToFile('php://output');

        $sheet = $writer->getCurrentSheet();
        $sheet->setName(__('admin.list.title'));

        // Baris judul ikut tergulir; tanpa ini kolom ke-20 tidak terbaca lagi
        // milik siapa begitu daftarnya di-scroll.
        $sheet->setSheetView((new SheetView)->setFreezeRow(2));

        $writer->addRow(Row::fromValues(array_column($columns, 'label'), $this->headerStyle()));

        $styles = $this->cellStyles();
        $written = 0;

        foreach ($export->rows() as $row) {
            $writer->addRow(new Row($this->cells($row, $columns, $styles)));
            $written++;
        }

        // Dipasang setelah barisnya ditulis karena butuh jumlah baris; XML-nya
        // baru dikarang saat close(), jadi urutan ini aman.
        $sheet->setAutoFilter(new AutoFilter(0, 1, count($columns) - 1, max(1, $written + 1)));

        $writer->close();
    }

    /**
     * @param  list<mixed>  $row
     * @param  list<array{label: string, type: string, width: float}>  $columns
     * @param  array<string, Style>  $styles
     * @return list<Cell>
     */
    private function cells(array $row, array $columns, array $styles): array
    {
        $cells = [];

        foreach ($row as $index => $value) {
            $type = $columns[$index]['type'] ?? SubmissionExport::TEXT;

            $cells[] = match (true) {
                $value === null || $value === '' => new Cell\EmptyCell(null, null),

                $value instanceof DateTimeInterface => new Cell\DateTimeCell(
                    $value,
                    $styles[$type === SubmissionExport::DATE ? 'date' : 'datetime']
                ),

                $type === SubmissionExport::NUMBER => new Cell\NumericCell((float) $value, null),

                // StringCell eksplisit, BUKAN Cell::fromValue(): fungsi itu
                // mengubah teks yang diawali "=" menjadi FormulaCell, dan nama
                // peserta datang dari formulir publik. Nilai teks di xlsx tidak
                // pernah dieksekusi, jadi tidak perlu ditambahi petik seperti CSV.
                default => new Cell\StringCell((string) $value, null),
            };
        }

        return $cells;
    }

    private function headerStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('162637')   // navy-900, sama dengan bar atas admin
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setShouldWrapText(false);
    }

    /**
     * Dibuat sekali lalu dipakai ulang: satu objek Style per sel akan
     * menggelembungkan daftar gaya di dalam berkasnya.
     *
     * @return array<string, Style>
     */
    private function cellStyles(): array
    {
        return [
            'datetime' => (new Style)->setFormat('dd/mm/yyyy hh:mm'),
            'date' => (new Style)->setFormat('dd/mm/yyyy'),
        ];
    }
}
