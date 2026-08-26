<?php

use App\Models\EmissionCategory;
use App\Models\EmissionFieldOption;
use App\Models\Leads;
use App\Models\ResultTier;
use App\Models\Submission;
use App\Services\CarbonCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Wizard kalkulator karbon.
 *
 * Langkah 1..n mengikuti baris di emission_categories; langkah terakhir
 * (n + 1) adalah "Isi Data Diri" yang datanya masuk ke tabel leads.
 *
 * Jawaban ditulis ke submission_values setiap kali user memilih opsi,
 * sehingga draft tetap utuh bila halaman ditutup di tengah pengisian.
 */
new #[Title('Kalkulator Karbon | Tokio Marine Green Campaign')] class extends Component
{
    /** Kunci session penyimpan draft yang sedang dikerjakan. */
    private const DRAFT_KEY = 'calculator.draft_uuid';

    public int $step = 1;

    /**
     * Jawaban yang sedang dipegang layar.
     *
     * Dikunci dengan ID, bukan `code`, karena `code` hanya unik per kategori
     * (unique-nya emission_category_id + code) sehingga dua kategori boleh
     * memakai kode field yang sama.
     *
     * @var array<int, int> id field => id opsi terpilih
     */
    public array $answers = [];

    public string $name = '';

    public string $email = '';

    public string $whatsapp = '';

    public string $dob = '';

    public string $gender = '';

    public string $intent = '';

    public bool $consent = false;

    public ?string $stepError = null;

    public function mount(): void
    {
        $submission = $this->submission();

        $this->step = max(1, min($this->totalSteps, $submission->current_step));

        $this->answers = $submission->values()
            ->whereNotNull('emission_field_option_id')
            ->pluck('emission_field_option_id', 'emission_field_id')
            ->all();
    }

    // -----------------------------------------------------------------
    // Data master
    // -----------------------------------------------------------------

    #[Computed]
    public function categories(): Collection
    {
        return EmissionCategory::query()
            ->active()
            ->ordered()
            ->withTranslation()
            ->with([
                'fields' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'fields.translations',
                'fields.options' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'fields.options.translations',
            ])
            ->get();
    }

    #[Computed]
    public function tiers(): Collection
    {
        return ResultTier::query()->active()->ordered()->withTranslation()->get();
    }

    #[Computed]
    public function totalSteps(): int
    {
        return $this->categories->count() + 1;
    }

    #[Computed]
    public function isPersonalStep(): bool
    {
        return $this->step === $this->totalSteps;
    }

    #[Computed]
    public function currentCategory(): ?EmissionCategory
    {
        return $this->categories->get($this->step - 1);
    }

    // -----------------------------------------------------------------
    // Skor berjalan
    // -----------------------------------------------------------------

    /** Opsi terpilih, diindeks berdasarkan id field. */
    #[Computed]
    public function selectedOptions(): Collection
    {
        $optionsById = $this->categories
            ->flatMap->fields
            ->flatMap->options
            ->keyBy(fn (EmissionFieldOption $option) => $option->id);

        return collect($this->answers)
            ->mapWithKeys(function ($optionId, $fieldId) use ($optionsById) {
                $option = $optionsById->get((int) $optionId);

                return $option ? [(int) $fieldId => $option] : [];
            });
    }

    #[Computed]
    public function score(): int
    {
        return app(CarbonCalculator::class)
            ->scoreForOptions($this->selectedOptions->values());
    }

    #[Computed]
    public function currentTier(): ?ResultTier
    {
        return $this->tiers->first(
            fn (ResultTier $tier) => $this->score >= $tier->min_score && $this->score <= $tier->max_score
        );
    }

    #[Computed]
    public function progressPercent(): int
    {
        return (int) round(($this->step - 1) / max(1, $this->totalSteps - 1) * 99);
    }

    /**
     * Ringkasan jawaban untuk sidebar, hanya kategori yang sudah dilewati.
     *
     * @return array<int, array{name: string, rows: array<int, array{label: string, value: string}>}>
     */
    #[Computed]
    public function summary(): array
    {
        $summary = [];

        foreach ($this->categories as $index => $category) {
            if ($index >= $this->step - 1) {
                continue;
            }

            $rows = [];

            foreach ($category->fields as $field) {
                $option = $this->selectedOptions->get($field->id);

                if (! $option) {
                    continue;
                }

                $rows[] = [
                    'label' => $field->tr('summary_label') ?: $field->tr('label'),
                    'value' => $option->summaryLabel(),
                ];
            }

            if ($rows !== []) {
                $summary[] = ['name' => $category->tr('name'), 'rows' => $rows];
            }
        }

        return $summary;
    }

    // -----------------------------------------------------------------
    // Aksi
    // -----------------------------------------------------------------

    /**
     * ID datang dari klien, jadi keduanya dicocokkan ulang ke field & opsi
     * milik kategori langkah ini sebelum apa pun disimpan.
     */
    public function select(int $fieldId, int $optionId): void
    {
        $field = $this->currentCategory?->fields->firstWhere('id', $fieldId);
        $option = $field?->options->firstWhere('id', $optionId);

        if (! $field || ! $option) {
            return;
        }

        $this->answers[$field->id] = $option->id;
        $this->stepError = null;

        $this->submission()->values()->updateOrCreate(
            ['emission_field_id' => $field->id],
            [
                'emission_category_id' => $field->emission_category_id,
                'emission_field_option_id' => $option->id,
                'points' => $option->points,
                'value_numeric' => $option->numeric_value,
                'kg_co2e_year' => $option->kg_co2e_year,
            ]
        );

        unset($this->selectedOptions, $this->score, $this->currentTier, $this->summary);
    }

    public function next(): void
    {
        if (! $this->isPersonalStep && ! $this->currentStepComplete()) {
            $this->stepError = __('calculator.validation.incomplete');

            return;
        }

        $this->stepError = null;
        $this->step = min($this->totalSteps, $this->step + 1);
        $this->persistStep();

        unset($this->currentCategory, $this->summary, $this->progressPercent, $this->isPersonalStep);
    }

    public function back(): void
    {
        $this->stepError = null;
        $this->step = max(1, $this->step - 1);
        $this->persistStep();

        unset($this->currentCategory, $this->summary, $this->progressPercent, $this->isPersonalStep);
    }

    /** Menyimpan lead, membekukan hasil, lalu pindah ke halaman hasil. */
    public function submitResult()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'whatsapp' => ['required', 'string', 'min:8', 'max:20'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
            'intent' => ['nullable', 'in:belum_tahu,mungkin,tentu'],
            'consent' => ['accepted'],
        ]);

        $submission = $this->submission();

        $lead = Leads::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_number' => $validated['whatsapp'],
            'dob' => $validated['dob'] ?: null,
            'gender' => $validated['gender'] ?: null,
            'intent' => $validated['intent'] ?: null,
            'locale' => app()->getLocale(),
            'consented_at' => Carbon::now(),
            'consent_version' => config('carbon-calculator.consent_version'),
        ]);

        $submission->forceFill([
            'lead_id' => $lead->id,
            'locale' => app()->getLocale(),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ])->save();

        app(CarbonCalculator::class)->finalise($submission->fresh(['values.field', 'values.option']));

        // Draft selesai: sesi berikutnya memulai submission baru.
        session()->forget(self::DRAFT_KEY);

        return $this->redirectRoute('calculator.result', ['uuid' => $submission->uuid], navigate: true);
    }

    // -----------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------

    private function currentStepComplete(): bool
    {
        $required = $this->currentCategory?->fields->where('is_required', true) ?? collect();

        return $required->every(fn ($field) => filled($this->answers[$field->id] ?? null));
    }

    private function persistStep(): void
    {
        $this->submission()->forceFill(['current_step' => $this->step])->save();
    }

    /** Draft yang sedang dikerjakan; dibuat sekali lalu disimpan di session. */
    private function submission(): Submission
    {
        $uuid = session(self::DRAFT_KEY);

        $submission = $uuid
            ? Submission::query()->draft()->where('uuid', $uuid)->first()
            : null;

        if (! $submission) {
            $submission = Submission::create([
                'locale' => app()->getLocale(),
                'status' => 'draft',
                'current_step' => 1,
            ]);

            session([self::DRAFT_KEY => $submission->uuid]);
        }

        return $submission;
    }
};
?>

<div class="flex min-h-screen flex-col bg-slate-50">
    <x-site-header active="calculator" />

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_17rem] lg:items-start">

            {{-- Kartu utama: panel kiri + isi wizard + bar aksi --}}
            <div class="overflow-hidden rounded-card border border-slate-200 bg-white shadow-sm">
                <div class="grid md:grid-cols-[13rem_minmax(0,1fr)]">

                    {{-- Panel kiri: gambar + judul + deskripsi langkah --}}
                    <aside class="space-y-4 bg-panel p-5">
                        <x-asset-image
                            :src="$this->currentCategory?->image_file"
                            :alt="$this->currentCategory?->tr('name') ?? __('calculator.personal.title')"
                            class="h-24 w-full rounded-lg"
                        />

                        <div class="space-y-2">
                            <h2 class="text-sm leading-snug font-bold text-slate-900">
                                {{ $this->currentCategory?->tr('panel_title') ?? __('calculator.personal.panel_title') }}
                            </h2>
                            <p class="text-xs leading-relaxed text-slate-600">
                                {{ $this->currentCategory?->tr('panel_description') ?? __('calculator.personal.panel_description') }}
                            </p>
                        </div>
                    </aside>

                    {{-- Kolom isi --}}
                    <section class="p-5 sm:p-6">
                        <h1 class="text-2xl font-bold text-brand-500">{{ __('calculator.title') }}</h1>

                        {{-- Progress bar dengan penanda tiap langkah --}}
                        <div class="mt-3">
                            <div class="relative h-1.5 rounded-full bg-slate-200">
                                <div
                                    class="h-1.5 rounded-full bg-brand-500 transition-all duration-500"
                                    style="width: {{ $this->progressPercent }}%"
                                ></div>
                                <div class="absolute inset-0 flex items-center justify-between px-0.5">
                                    @for ($i = 1; $i <= $this->totalSteps; $i++)
                                        <span @class([
                                            'size-2 rounded-full ring-2 ring-white',
                                            'bg-brand-500' => $i <= $this->step,
                                            'bg-slate-300' => $i > $this->step,
                                        ])></span>
                                    @endfor
                                </div>
                            </div>
                            <p class="mt-1.5 text-[0.7rem] font-semibold text-brand-500">
                                {{ __('calculator.to_complete', ['percent' => $this->progressPercent]) }}
                            </p>
                        </div>

                        {{-- Rail bernomor + pertanyaan --}}
                        <div class="mt-5 flex gap-4">
                            <div class="relative hidden w-7 shrink-0 flex-col items-center justify-between py-1 sm:flex">
                                <div class="absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 bg-brand-200"></div>
                                @foreach ($this->categories as $index => $category)
                                    <span
                                        @class([
                                            'relative z-10 grid size-6 place-items-center rounded-full text-[0.65rem] font-bold transition',
                                            'bg-brand-500 text-white' => $index + 1 <= $this->step,
                                            'border-2 border-brand-200 bg-white text-brand-400' => $index + 1 > $this->step,
                                            'ring-4 ring-brand-100' => $index + 1 === $this->step,
                                        ])
                                        title="{{ $category->tr('name') }}"
                                    >{{ $index + 1 }}</span>
                                @endforeach
                            </div>

                            <div class="min-w-0 flex-1">
                                @if ($this->isPersonalStep)
                                    @include('pages.partials.calculator-personal')
                                @else
                                    <h2 class="text-lg font-bold text-brand-500">
                                        {{ $this->currentCategory->tr('name') }}
                                    </h2>

                                    <div class="mt-4 space-y-6">
                                        @foreach ($this->currentCategory->fields as $field)
                                            <fieldset>
                                                <legend class="text-sm font-semibold text-slate-800">
                                                    {{ $field->tr('label') }}
                                                </legend>

                                                @if ($field->tr('helper_text'))
                                                    <p class="mt-1 text-xs text-slate-500">{{ $field->tr('helper_text') }}</p>
                                                @endif

                                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                    @foreach ($field->options as $option)
                                                        @php $selected = (int) ($answers[$field->id] ?? 0) === $option->id; @endphp

                                                        <button
                                                            type="button"
                                                            wire:key="opt-{{ $option->id }}"
                                                            wire:click="select({{ $field->id }}, {{ $option->id }})"
                                                            aria-pressed="{{ $selected ? 'true' : 'false' }}"
                                                            @class([
                                                                'group relative flex w-full rounded-lg border p-3 text-left transition',
                                                                'border-brand-500 bg-brand-50 ring-1 ring-brand-500' => $selected,
                                                                'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/40' => ! $selected,
                                                                'flex-col gap-2 pr-8' => $field->isCardStyle(),
                                                                'items-center justify-between gap-3' => ! $field->isCardStyle(),
                                                            ])
                                                        >
                                                            @if ($field->isCardStyle())
                                                                <x-asset-image
                                                                    :src="$option->image_file"
                                                                    :alt="$option->tr('label')"
                                                                    class="h-12 w-16 rounded-md"
                                                                />
                                                            @endif

                                                            <span @class([
                                                                'text-xs leading-snug font-medium',
                                                                'text-brand-700' => $selected,
                                                                'text-slate-700' => ! $selected,
                                                            ])>{{ $option->tr('label') }}</span>

                                                            <span @class([
                                                                'grid size-4 shrink-0 place-items-center rounded-full border-2 transition',
                                                                'border-brand-500' => $selected,
                                                                'border-slate-300 group-hover:border-brand-300' => ! $selected,
                                                                'absolute top-3 right-3' => $field->isCardStyle(),
                                                            ])>
                                                                @if ($selected)
                                                                    <span class="size-2 rounded-full bg-brand-500"></span>
                                                                @endif
                                                            </span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </fieldset>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($stepError)
                                    <p class="mt-4 rounded-lg bg-tm-red/10 px-3 py-2 text-xs font-medium text-tm-red">
                                        {{ $stepError }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>

                {{-- Bar aksi --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white px-5 py-4">
                    @if ($this->step === 1)
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                            {{ __('calculator.nav.back_home') }}
                        </a>
                    @else
                        <button type="button" wire:click="back" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                            {{ __('calculator.nav.back_to', ['step' => $this->categories->get($this->step - 2)?->tr('name') ?? '']) }}
                        </button>
                    @endif

                    @if ($this->isPersonalStep)
                        <button
                            type="button"
                            wire:click="submitResult"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 rounded-lg bg-deep-600 px-5 py-2 text-xs font-semibold text-white transition hover:bg-deep-700 disabled:opacity-60"
                        >
                            {{ __('calculator.nav.see_result') }}
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                        </button>
                    @else
                        <button
                            type="button"
                            wire:click="next"
                            class="inline-flex items-center gap-2 rounded-lg bg-deep-600 px-5 py-2 text-xs font-semibold text-white transition hover:bg-deep-700"
                        >
                            @if ($this->step + 1 === $this->totalSteps)
                                {{ __('calculator.nav.to_personal_data') }}
                            @else
                                {{ __('calculator.nav.continue_to', ['step' => $this->categories->get($this->step)?->tr('name') ?? '']) }}
                            @endif
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Sidebar skor + ringkasan jawaban --}}
            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="rounded-card border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-center text-sm font-bold text-slate-900">{{ __('calculator.score_card.title') }}</h2>

                    <x-score-gauge :score="$this->score" :tier="$this->currentTier" class="mt-3" />

                    <x-tier-legend :tiers="$this->tiers" class="mt-4" />
                </div>

                @foreach ($this->summary as $group)
                    <div class="rounded-card border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-900">{{ $group['name'] }}</h3>
                        <dl class="mt-2 divide-y divide-slate-100">
                            @foreach ($group['rows'] as $row)
                                <div class="flex items-start justify-between gap-3 py-1.5">
                                    <dt class="text-[0.7rem] text-slate-500">{{ $row['label'] }}</dt>
                                    <dd class="text-right text-[0.7rem] font-semibold text-slate-800">{{ $row['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </aside>
        </div>
    </main>

    <x-site-footer />
</div>
