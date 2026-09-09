<?php

namespace App\Mail;

use App\Models\Submission;
use App\Support\ResultReport;
use App\Support\ResultText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email "hasilmu sudah siap" yang dikirim begitu wizard selesai.
 *
 * Isinya ringkasan seperlunya (skor, tier, total ton) plus dua tautan:
 * halaman hasil dan halaman laporan siap cetak. Keduanya berkunci uuid dan
 * tidak butuh login — itulah cara peserta kembali ke hasilnya setelah tab
 * ditutup, karena aplikasi ini memang tidak punya akun pengunjung.
 *
 * Angkanya dibaca dari App\Support\ResultReport, sumber yang sama dengan
 * halaman hasil dan laporan, supaya email tidak pernah menyebut angka lain.
 *
 * Sengaja TIDAK implements ShouldQueue: hosting kampanye ini tanpa cron
 * sehingga queue-nya `sync` (lihat deploy/DEPLOY.md). Pengirimannya terjadi
 * di dalam request dan kegagalannya ditelan App\Services\ResultEmailer.
 */
class ResultReadyMail extends Mailable
{
    use SerializesModels;

    private ?ResultReport $report = null;

    public function __construct(public readonly Submission $submission) {}

    public function envelope(): Envelope
    {
        $report = $this->report();

        return new Envelope(
            subject: __('mail.result.subject', ['name' => $report->firstName()]),
        );
    }

    public function content(): Content
    {
        $report = $this->report();
        $tier = $report->tier();

        return new Content(
            view: 'emails.result-ready',
            text: 'emails.result-ready-text',
            with: [
                'name' => $report->firstName(),
                'score' => $report->score(),
                'tierLabel' => $tier?->tr('label'),
                'tierColor' => $tier?->color ?: '#0d9488',
                'tierHeadline' => $tier ? ResultText::render($tier->tr('headline'), ['name' => $report->firstName()]) : null,
                'totalTon' => ResultText::tonCompact($report->totalKg()),
                'recommendations' => $report->recommendations()
                    ->map(fn ($recommendation) => ResultText::render($recommendation->tr('body'), ['name' => $report->firstName()]))
                    ->all(),
                'resultUrl' => route('calculator.result', ['uuid' => $this->submission->uuid]),
                // `cetak=1` membuat halaman laporan langsung membuka dialog
                // cetak, jalur yang sama dengan tombol "Download Result".
                'reportUrl' => route('calculator.report', ['uuid' => $this->submission->uuid, 'cetak' => 1]),
            ],
        );
    }

    /** Dibaca dua kali (subjek dan isi), jadi hasilnya disimpan. */
    private function report(): ResultReport
    {
        return $this->report ??= ResultReport::forUuid($this->submission->uuid)
            ?? throw new \RuntimeException("Submission {$this->submission->uuid} belum punya hasil yang bisa dikirim.");
    }
}
