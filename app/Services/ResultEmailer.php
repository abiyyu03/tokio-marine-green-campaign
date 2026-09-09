<?php

namespace App\Services;

use App\Mail\ResultReadyMail;
use App\Models\Submission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Pengiriman email hasil ke peserta.
 *
 * Dipisahkan dari komponen wizard karena dua alasan: pengiriman ulang nanti
 * (dari area admin atau tinker) memakai jalur yang sama persis, dan aturan
 * "kegagalan email tidak boleh membatalkan hasil" jadi punya satu tempat.
 *
 * Queue di hosting kampanye ini `sync` (tanpa cron, lihat deploy/DEPLOY.md),
 * jadi pengiriman terjadi di dalam request yang menekan tombol terakhir.
 * Konsekuensinya ditangani di sini: seluruh Throwable ditelan dan dicatat,
 * sebab data peserta sudah tersimpan dan halaman hasil sudah sah — SMTP yang
 * sedang rewel tidak boleh berubah menjadi layar error.
 */
class ResultEmailer
{
    /**
     * @param  bool  $force  kirim lagi walau penanda terkirim sudah terisi
     * @return bool apakah email benar-benar berangkat pada pemanggilan ini
     */
    public function send(Submission $submission, bool $force = false): bool
    {
        $lead = $submission->lead;

        if (! $lead?->email) {
            return false;
        }

        if ($submission->result_email_sent_at && ! $force) {
            return false;
        }

        try {
            Mail::to($lead->email, $lead->name)
                // Bahasa email mengikuti bahasa saat peserta mengisi, bukan
                // bahasa request yang kebetulan sedang aktif.
                ->locale($submission->locale ?: config('app.locale'))
                ->send(new ResultReadyMail($submission));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim email hasil kalkulator.', [
                'submission_uuid' => $submission->uuid,
                'mailer' => config('mail.default'),
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        $submission->forceFill(['result_email_sent_at' => Carbon::now()])->save();

        return true;
    }
}
