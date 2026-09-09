<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Penyaring daftar peserta.
 *
 * Dipakai bersama oleh halaman daftar dan kedua ekspor. Itu bukan kerapian
 * belaka: kalau klausa where-nya ditulis dua kali, cepat atau lambat berkas
 * yang terunduh akan berbeda isi dari yang dilihat di layar, dan bedanya tidak
 * akan terlihat sampai ada yang membandingkan jumlah barisnya.
 */
final class SubmissionFilter
{
    public function __construct(
        public readonly string $search = '',
        public readonly string $status = '',
        public readonly ?int $tierId = null,
        public readonly string $from = '',
        public readonly string $until = '',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $tier = $request->query('tier');

        return new self(
            search: trim((string) $request->query('q', '')),
            status: (string) $request->query('status', ''),
            tierId: is_numeric($tier) ? (int) $tier : null,
            from: (string) $request->query('dari', ''),
            until: (string) $request->query('sampai', ''),
        );
    }

    /**
     * Hanya klausa where. Pengurutan sengaja tidak ditentukan di sini: daftar
     * memakai latest(), ekspor memakai lazyById() yang mengganti urutannya
     * demi chunking yang stabil.
     *
     * @param  Builder<\App\Models\Submission>  $query
     * @return Builder<\App\Models\Submission>
     */
    public function apply(Builder $query): Builder
    {
        return $query
            ->when($this->search !== '', function (Builder $q) {
                $term = '%'.self::escapeLike($this->search).'%';

                $q->whereHas('lead', fn (Builder $lead) => $lead
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('whatsapp_number', 'like', $term));
            })
            ->when(
                in_array($this->status, ['draft', 'completed'], true),
                fn (Builder $q) => $q->where('status', $this->status)
            )
            ->when(
                $this->tierId !== null,
                fn (Builder $q) => $q->whereHas(
                    'result',
                    fn (Builder $r) => $r->where('result_tier_id', $this->tierId)
                )
            )
            ->when(
                ($start = $this->startsAt()) !== null,
                fn (Builder $q) => $q->where('created_at', '>=', $start)
            )
            ->when(
                ($end = $this->endsAt()) !== null,
                fn (Builder $q) => $q->where('created_at', '<=', $end)
            );
    }

    public function isEmpty(): bool
    {
        return $this->search === ''
            && $this->status === ''
            && $this->tierId === null
            && $this->from === ''
            && $this->until === '';
    }

    /**
     * Parameter untuk membangun tautan ekspor, hanya yang terisi.
     *
     * @return array<string, string|int>
     */
    public function toQuery(): array
    {
        return array_filter([
            'q' => $this->search,
            'status' => $this->status,
            'tier' => $this->tierId,
            'dari' => $this->from,
            'sampai' => $this->until,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Awal hari pertama dalam UTC.
     *
     * Kolom created_at tersimpan UTC (config('app.timezone') memang UTC),
     * sementara admin memilih tanggal dalam waktu setempat. Tanpa konversi
     * ini, filter "dari 7 September" akan melewatkan seluruh pengisian antara
     * 00.00-07.00 WIB pada tanggal itu.
     */
    private function startsAt(): ?Carbon
    {
        return self::boundary($this->from, startOfDay: true);
    }

    private function endsAt(): ?Carbon
    {
        return self::boundary($this->until, startOfDay: false);
    }

    private static function boundary(string $date, bool $startOfDay): ?Carbon
    {
        // Nilainya datang dari query string, jadi bentuknya diperiksa dulu.
        // Tanggal yang tidak terbaca akan membuat MySQL mengembalikan nol
        // baris tanpa pesan apa pun — sulit ditelusuri.
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return null;
        }

        if (! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        $zone = config('carbon-calculator.display_timezone', 'UTC');
        $moment = Carbon::createFromFormat('Y-m-d', $date, $zone);

        return ($startOfDay ? $moment->startOfDay() : $moment->endOfDay())->utc();
    }

    /**
     * "%" dan "_" adalah wildcard LIKE. Tanpa di-escape, mengetik "%" di kotak
     * pencarian akan mencocokkan semua baris — hasil salah yang senyap.
     */
    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
