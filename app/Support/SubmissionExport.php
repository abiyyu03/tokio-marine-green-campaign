<?php

namespace App\Support;

use App\Models\EmissionCategory;
use App\Models\Submission;
use Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Sumber tunggal isi ekspor peserta.
 *
 * CSV dan Excel membaca kolom dan baris dari sini, bukan menyusunnya
 * sendiri-sendiri. Kalau tidak, cepat atau lambat satu format akan punya kolom
 * yang tidak ada di format lain — dan tidak akan ketahuan sampai ada yang
 * membandingkan dua berkas berdampingan.
 *
 * Nilainya sengaja dikembalikan mentah (float, Carbon, string), bukan sudah
 * jadi teks: CSV memang butuh teks, tapi Excel butuh angka sebagai angka dan
 * tanggal sebagai tanggal supaya bisa disortir dan dijumlah. Masing-masing
 * penulis yang memformat sesuai `type` kolom.
 */
final class SubmissionExport
{
    public const TEXT = 'text';

    public const NUMBER = 'number';

    public const DATETIME = 'datetime';

    public const DATE = 'date';

    /** @var Collection<int, EmissionCategory>|null */
    private ?Collection $categories = null;

    public function __construct(public readonly SubmissionFilter $filter) {}

    public static function fromRequest(Request $request): self
    {
        return new self(SubmissionFilter::fromRequest($request));
    }

    /** Zona waktu tampilan; kolom created_at sendiri tersimpan UTC. */
    public function timezone(): string
    {
        return config('carbon-calculator.display_timezone', 'UTC');
    }

    /**
     * Definisi kolom, urut kiri ke kanan.
     *
     * `width` dipakai Excel saja — CSV mengabaikannya.
     *
     * @return list<array{label: string, type: string, width: float}>
     */
    public function columns(): array
    {
        $columns = [
            ['label' => __('admin.columns.uuid'), 'type' => self::TEXT, 'width' => 38],
            ['label' => __('admin.columns.status'), 'type' => self::TEXT, 'width' => 14],
            ['label' => __('admin.columns.created_at'), 'type' => self::DATETIME, 'width' => 17],
            ['label' => __('admin.columns.completed_at'), 'type' => self::DATETIME, 'width' => 17],
            ['label' => __('admin.columns.name'), 'type' => self::TEXT, 'width' => 24],
            ['label' => __('admin.columns.email'), 'type' => self::TEXT, 'width' => 28],
            ['label' => __('admin.columns.whatsapp'), 'type' => self::TEXT, 'width' => 16],
            ['label' => __('admin.columns.dob'), 'type' => self::DATE, 'width' => 13],
            ['label' => __('admin.columns.gender'), 'type' => self::TEXT, 'width' => 13],
            ['label' => __('admin.columns.intent'), 'type' => self::TEXT, 'width' => 14],
            ['label' => __('admin.columns.locale'), 'type' => self::TEXT, 'width' => 9],
            ['label' => __('admin.columns.consented_at'), 'type' => self::DATETIME, 'width' => 17],
            ['label' => __('admin.columns.consent_version'), 'type' => self::TEXT, 'width' => 12],
            ['label' => __('admin.columns.score'), 'type' => self::NUMBER, 'width' => 8],
            ['label' => __('admin.columns.tier'), 'type' => self::TEXT, 'width' => 16],
            ['label' => __('admin.columns.total_ton'), 'type' => self::NUMBER, 'width' => 16],
            ['label' => __('admin.columns.total_kg'), 'type' => self::NUMBER, 'width' => 16],
            ['label' => __('admin.columns.raw_kg'), 'type' => self::NUMBER, 'width' => 18],
        ];

        foreach ($this->categories() as $category) {
            $columns[] = [
                'label' => $category->tr('name').' (kg CO2e)',
                'type' => self::NUMBER,
                'width' => 18,
            ];
        }

        return $columns;
    }

    /**
     * Baris peserta, satu per satu.
     *
     * lazyById(): memori tetap datar berapa pun jumlah pesertanya, dan kuncinya
     * stabil walau ada pengisian baru masuk saat ekspor sedang berjalan.
     *
     * @return Generator<int, list<mixed>>
     */
    public function rows(): Generator
    {
        $query = $this->filter->apply(Submission::query())
            ->with(['lead', 'result.tier.translations', 'categoryResults']);

        $zone = $this->timezone();

        foreach ($query->lazyById(500) as $submission) {
            yield $this->row($submission, $zone);
        }
    }

    /** Nama berkas, dengan cap waktu dalam zona waktu tampilan. */
    public function filename(string $extension): string
    {
        return 'peserta-tokio-marine-'
            .now()->timezone($this->timezone())->format('Y-m-d-Hi')
            .'.'.$extension;
    }

    /**
     * Jejak audit: berkas ini berisi nama, email, dan nomor WhatsApp seluruh
     * peserta yang cocok. Yang dicatat pelakunya dan filternya — BUKAN isi
     * barisnya, supaya log tidak jadi salinan kedua data pribadi.
     */
    public function logAudit(Request $request, string $format): void
    {
        Log::info('admin.export.submissions', [
            'format' => $format,
            'user_id' => $request->user()?->id,
            'filter' => $this->filter->toQuery(),
            'ip' => $request->ip(),
        ]);
    }

    /** @return Collection<int, EmissionCategory> */
    private function categories(): Collection
    {
        return $this->categories ??= EmissionCategory::query()
            ->active()->ordered()->withTranslation()->get();
    }

    /** @return list<mixed> */
    private function row(Submission $submission, string $zone): array
    {
        $lead = $submission->lead;
        $result = $submission->result;
        $perCategory = $submission->categoryResults->keyBy('emission_category_id');

        $values = [
            $submission->uuid,
            __('admin.status.'.$submission->status),
            $submission->created_at?->timezone($zone),
            $submission->completed_at?->timezone($zone),
            $lead?->name,
            $lead?->email,
            $lead?->whatsapp_number,
            $lead?->dob,
            $lead?->gender ? __('admin.gender.'.$lead->gender) : null,
            $lead?->intent ? __('admin.intent.'.$lead->intent) : null,
            $lead?->locale,
            $lead?->consented_at?->timezone($zone),
            $lead?->consent_version,
            $result?->score,
            $result?->tier?->tr('label'),
            $result ? round($result->total_kg_co2e_year / 1000, 3) : null,
            $result?->total_kg_co2e_year,
            $result?->raw_kg_co2e_year,
        ];

        foreach ($this->categories() as $category) {
            $values[] = $perCategory->get($category->id)?->kg_co2e_year;
        }

        return $values;
    }
}
