<?php

use App\Models\EmissionCategory;
use App\Models\EmissionField;
use App\Models\EmissionFieldOption;
use App\Models\Leads;
use App\Models\ResultTier;
use App\Models\Submission;
use App\Services\CarbonCalculator;
use App\Services\ResultEmailer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Wizard kalkulator karbon.
 *
 * Langkah 1..n dibaca dari emission_categories, jadi menambah atau mengubah
 * pertanyaan cukup lewat seeder — tidak ada daftar opsi yang ditulis ulang di
 * layar ini. Langkah terakhir (n + 1) adalah "Isi Data Diri" yang datanya
 * masuk ke tabel leads.
 *
 * Jawaban ditulis ke submission_values begitu user memilih opsi, sehingga
 * draft tetap utuh bila halaman ditutup di tengah pengisian, dan skor di
 * sidebar selalu berasal dari `points` yang sama dengan yang dipakai
 * App\Services\CarbonCalculator saat hasil dibekukan.
 */
new #[Title('Hitung Jejak Karbonmu | Tokio Marine Green Campaign')] class extends Component
{
    /** Kunci session penyimpan draft yang sedang dikerjakan. */
    private const DRAFT_KEY = 'calculator.draft_uuid';

    public int $step = 1;

    /**
     * Jawaban yang sedang dipegang layar.
     *
     * Dikunci dengan ID, bukan `code`, karena `code` hanya unik per kategori
     * sehingga dua kategori boleh memakai kode field yang sama.
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

    /** Uuid hasil yang sudah dibekukan, penjaga submit ganda. */
    public ?string $completedUuid = null;

    public function mount(): void
    {
        $submission = $this->submission();

        $this->step = max(1, min($this->totalSteps, $submission->current_step));
        $this->answers = $this->storedAnswers();
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
        return app(CarbonCalculator::class)->scoreForOptions($this->selectedOptions->values());
    }

    #[Computed]
    public function currentTier(): ?ResultTier
    {
        return $this->tiers->first(
            fn (ResultTier $tier) => $this->score >= $tier->min_score && $this->score <= $tier->max_score
        );
    }

    /** 30% - 60% - 90% untuk tiga langkah emisi, lalu 99% di langkah data diri. */
    #[Computed]
    public function progressPercent(): int
    {
        if ($this->step >= $this->totalSteps) {
            return 99;
        }

        return (int) round($this->step * 90 / max(1, $this->totalSteps - 1));
    }

    /**
     * Posisi titik penanda di atas progress bar: satu di 0%, satu di tiap
     * langkah emisi, lalu satu terakhir di 99%.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function progressMarkers(): array
    {
        $markers = [];

        for ($i = 0; $i < $this->totalSteps; $i++) {
            $markers[] = (int) round($i * 90 / max(1, $this->totalSteps - 1));
        }

        $markers[] = 99;

        return $markers;
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
     * Dipanggil Livewire setiap kali jawaban berubah.
     *
     * `$key` berisi id field saat satu radio yang berubah, tapi bernilai null
     * saat Livewire mengganti seluruh array `answers` sekaligus — karena itu
     * keduanya harus ditangani.
     *
     * Kunci dan nilainya datang dari klien, jadi selalu dicocokkan ulang ke
     * data master sebelum apa pun disimpan. Pencocokan sengaja tidak dibatasi
     * ke kategori langkah ini saja: jawaban langkah sebelumnya harus tetap
     * boleh ada di `answers`.
     */
    public function updatedAnswers(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            $this->reconcileAnswers();

            return;
        }

        $fieldId = (int) $key;
        $field = $this->fieldFor($fieldId);
        $option = $field?->options->firstWhere('id', (int) $value);

        // Pasangan field/opsi yang tidak dikenal cukup dibuang dengan
        // mengembalikan seluruh jawaban ke isi draft, sehingga layar dan
        // submission_values tidak pernah berbeda isi.
        if (! $field || ! $option) {
            $this->answers = $this->storedAnswers();
            $this->forgetAnswerState();

            return;
        }

        $this->answers[$fieldId] = $option->id;
        $this->stepError = null;
        $this->storeAnswer($field, $option);

        $this->forgetAnswerState();
    }

    /**
     * Menyaring seluruh isi `answers` terhadap data master lalu menuliskan
     * yang lolos ke draft. Dipakai saat Livewire mengganti array sekaligus.
     */
    private function reconcileAnswers(): void
    {
        $valid = [];

        foreach ($this->answers as $fieldId => $optionId) {
            $field = $this->fieldFor((int) $fieldId);
            $option = $field?->options->firstWhere('id', (int) $optionId);

            if (! $field || ! $option) {
                continue;
            }

            $valid[$field->id] = $option->id;
            $this->storeAnswer($field, $option);
        }

        $this->answers = $valid;
        $this->stepError = null;
        $this->forgetAnswerState();
    }

    public function nextStep(): void
    {
        if (! $this->isPersonalStep && ! $this->stepComplete($this->currentCategory)) {
            $this->stepError = __('calculator.validation.incomplete');

            return;
        }

        $this->stepError = null;
        $this->step = min($this->totalSteps, $this->step + 1);
        $this->persistStep();

        $this->forgetStepState();
    }

    public function previousStep(): void
    {
        $this->stepError = null;
        $this->step = max(1, $this->step - 1);
        $this->persistStep();

        $this->forgetStepState();
    }

    /**
     * Aturan langkah data diri.
     *
     * Dipakai dua kali: sekali saat submit, dan sekali per field lewat
     * `updated()`. Pesannya tidak ditulis di sini melainkan di
     * lang/{locale}/validation.php (blok `custom` + `attributes`), supaya
     * kalimat error ikut berganti bahasa seperti teks halaman lainnya.
     *
     * @return array<string, array<int, string>>
     */
    private function personalRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            // Nomor disimpan tanpa +62 (lihat normalisedWhatsapp), jadi yang
            // diterima hanya angka — huruf akan hilang diam-diam saat
            // dinormalisasi dan menghasilkan nomor yang salah.
            'whatsapp' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[0-9][0-9 ()-]*$/'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
            'intent' => ['nullable', 'in:belum_tahu,mungkin,tentu'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * Validasi ulang satu field begitu isiannya berubah (wire:model.blur).
     *
     * Tanpa ini pesan error langkah data diri baru hilang setelah tombol
     * submit ditekan lagi — jadi field yang sudah dibetulkan tetap terlihat
     * merah dan pesannya terbaca seolah isian masih kosong.
     */
    public function updated(string $property): void
    {
        $rules = $this->personalRules();

        if (array_key_exists($property, $rules)) {
            $this->validateOnly($property, $rules);
        }
    }

    /** Menyimpan lead, membekukan hasil, lalu pindah ke halaman hasil. */
    public function submitLeads()
    {
        // Klik kedua pada tombol yang sama tidak boleh membuat hasil baru.
        if ($this->completedUuid) {
            return $this->redirectRoute('calculator.result', ['uuid' => $this->completedUuid], navigate: true);
        }

        $validated = $this->validate($this->personalRules());

        $submission = $this->submission();

        // Kelengkapan diperiksa dari isi draft, BUKAN dari `answers` di layar.
        // Keduanya bisa berbeda: draftnya mungkin sudah hilang (dihapus, atau
        // sesi berpindah) sementara layar masih memegang jawaban lama. Kalau
        // yang dipercaya state layar, hasil kosong bisa ikut dibekukan dan
        // terlihat sah di halaman hasil — skor 0 dengan badge "Dampak Ringan".
        $stored = $this->storedAnswers();
        $unfinished = $this->categories->first(fn (EmissionCategory $category) => ! $this->stepComplete($category, $stored));

        if ($unfinished) {
            $this->answers = $stored;
            $index = $this->categories->search(fn (EmissionCategory $c) => $c->id === $unfinished->id);
            $this->step = $index === false ? 1 : $index + 1;
            $this->stepError = __('calculator.validation.incomplete');
            $this->forgetAnswerState();
            $this->forgetStepState();

            return null;
        }

        $lead = Leads::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_number' => $this->normalisedWhatsapp(),
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

        // Email hasil dikirim di dalam request ini (queue `sync` di hosting
        // kampanye). ResultEmailer menelan kegagalannya: hasil sudah tersimpan
        // dan halaman hasil tetap dibuka walau SMTP sedang bermasalah.
        app(ResultEmailer::class)->send($submission->fresh('lead'));

        // Draft selesai: sesi berikutnya memulai submission baru.
        $this->completedUuid = $submission->uuid;
        session()->forget(self::DRAFT_KEY);

        return $this->redirectRoute('calculator.result', ['uuid' => $submission->uuid], navigate: true);
    }

    // -----------------------------------------------------------------
    // Internal
    // -----------------------------------------------------------------

    /**
     * @param  array<int, int>|null  $answers  sumber jawaban; default state layar
     */
    private function stepComplete(?EmissionCategory $category, ?array $answers = null): bool
    {
        $answers ??= $this->answers;
        $required = $category?->fields->where('is_required', true) ?? collect();

        return $required->every(fn ($field) => filled($answers[$field->id] ?? null));
    }

    /** Field mana pun di seluruh langkah, dicari berdasarkan id. */
    private function fieldFor(int $fieldId): ?EmissionField
    {
        return $this->categories->flatMap->fields->firstWhere('id', $fieldId);
    }

    /** Menulis satu jawaban ke draft; satu baris per field. */
    private function storeAnswer(EmissionField $field, EmissionFieldOption $option): void
    {
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
    }

    /**
     * Jawaban yang benar-benar tersimpan di draft.
     *
     * @return array<int, int>
     */
    private function storedAnswers(): array
    {
        return $this->submission()->values()
            ->whereNotNull('emission_field_option_id')
            ->pluck('emission_field_option_id', 'emission_field_id')
            ->map(fn ($optionId) => (int) $optionId)
            ->all();
    }

    private function forgetAnswerState(): void
    {
        unset($this->selectedOptions, $this->score, $this->currentTier, $this->summary);
    }

    private function forgetStepState(): void
    {
        unset(
            $this->currentCategory,
            $this->isPersonalStep,
            $this->progressPercent,
            $this->summary,
        );
    }

    private function persistStep(): void
    {
        $this->submission()->forceFill(['current_step' => $this->step])->save();
    }

    /** "08123456789" dan "8123456789" sama-sama disimpan sebagai "628123456789". */
    private function normalisedWhatsapp(): string
    {
        $digits = preg_replace('/\D+/', '', $this->whatsapp) ?? '';

        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        return '62'.ltrim($digits, '0');
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

<div class="flex min-h-screen flex-col bg-[#f4f7f9]">
    <x-site-header active="calculator" />

    {{-- HEADER ATAS WIZARD --}}
    <div class="bg-white w-full border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#1e293b]">{{ __('calculator.title') }}</h1>

            {{-- Progress Bar --}}
            <div class="mt-6 w-full lg:w-3/4">
                @php($progress = $this->progressPercent)
                <div class="relative h-2 w-full rounded-full bg-slate-200">
                    {{-- Bar hijau yang berjalan --}}
                    <div class="absolute left-0 top-0 h-2 rounded-full bg-[#0d9488] transition-all duration-500" style="width: {{ $progress }}%"></div>

                    {{-- Titik penanda tiap langkah --}}
                    <div class="absolute inset-0">
                        @foreach ($this->progressMarkers as $marker)
                            <span
                                class="absolute top-1/2 -translate-y-1/2 size-1.5 rounded-full {{ $progress >= $marker ? 'bg-white ring-2 ring-[#0d9488]' : 'bg-slate-300' }}"
                                style="left: {{ $marker }}%;"
                            ></span>
                        @endforeach
                    </div>
                </div>
                <p class="mt-2 text-xs sm:text-sm text-[#0d9488]">
                    <strong class="font-bold">{{ $progress }}%</strong>
                    <span class="font-medium text-[#0d9488]/80">to complete</span>
                </p>
            </div>
        </div>
    </div>

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">

        {{-- CARD INDIKATOR (hanya tampil di langkah emisi) --}}
        @unless ($this->isPersonalStep)
        <div class="mb-8 overflow-hidden rounded-2xl bg-white px-4 py-8 shadow-sm sm:px-16 sm:py-10">
            <div class="relative mx-auto flex max-w-4xl items-start justify-between">

                {{-- Garis Penghubung --}}
                <div class="absolute left-10 right-10 top-7 h-[2px] bg-slate-200 sm:left-16 sm:right-16 sm:top-8"></div>
                <div class="absolute left-10 top-7 h-[2px] bg-[#0d9488] transition-all duration-500 sm:left-16 sm:top-8"
                     style="width: {{ ($this->step - 1) / max(1, $this->categories->count() - 1) * 100 }}%; max-width: calc(100% - 2.5rem);">
                </div>

                @foreach ($this->categories as $index => $category)
                    @php($position = $index + 1)
                    <div class="relative z-10 flex w-20 flex-col items-center gap-2 sm:w-32 sm:gap-3" wire:key="indicator-{{ $category->id }}">
                        <div class="flex size-14 items-center justify-center rounded-full {{ $this->step >= $position ? 'border-[3px] border-[#0d9488] bg-white p-1' : 'bg-[#e2e8f0]' }} transition-all sm:size-16">
                            <div class="flex size-full items-center justify-center rounded-full {{ $this->step > $position ? 'bg-white border-2 border-[#0d9488]' : ($this->step === $position ? 'bg-[#0d9488]' : 'bg-transparent') }}">
                                <x-option-icon
                                    :name="$category->icon"
                                    class="size-5 sm:size-6 {{ $this->step > $position ? 'text-[#0d9488]' : ($this->step === $position ? 'text-white' : 'text-slate-400') }}"
                                />
                            </div>
                        </div>
                        <span class="text-center text-[10px] leading-tight {{ $this->step >= $position ? 'font-bold text-[#0d9488]' : 'font-medium text-slate-500' }} sm:text-sm sm:leading-snug">
                            {{ $category->tr('name') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endunless

        {{-- LAYOUT UTAMA --}}
        <div class="grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">

            {{-- KOLOM KIRI: FORMULIR WIZARD --}}
            <div class="{{ $this->isPersonalStep ? 'rounded-2xl bg-white p-6 shadow-sm border border-slate-100' : 'space-y-6' }}">

                {{-- Ringkasan error: di layar sempit field yang bermasalah bisa
                     berada jauh di bawah lipatan, jadi pesannya diulang di atas. --}}
                @if ($stepError || $errors->any())
                    <div role="alert" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 border border-red-200 mb-6">
                        <p class="font-semibold">{{ $stepError ?? __('calculator.validation.form') }}</p>
                        @if ($errors->any())
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                {{-- ==================== LANGKAH EMISI ==================== --}}
                @if ($this->currentCategory)
                    <h2 class="text-xl sm:text-2xl font-bold text-[#0d9488] mb-2 border-b pb-4 border-slate-200">
                        {{ $this->currentCategory->tr('name') }}
                    </h2>

                    @foreach ($this->currentCategory->fields as $index => $field)
                        <fieldset class="{{ $index > 0 ? 'pt-6 ' : '' }}space-y-4" wire:key="field-{{ $field->id }}">
                            <legend class="text-sm sm:text-base font-bold text-slate-800 mb-4">{{ $field->tr('label') }}</legend>

                            @if ($field->isCardStyle())
                                {{-- Kartu bergambar: moda transportasi --}}
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                                    @foreach ($field->options as $option)
                                        @php($checked = ($answers[$field->id] ?? null) == $option->id)
                                        <label
                                            wire:key="option-{{ $option->id }}"
                                            class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 p-3 sm:p-4 transition-all {{ $checked ? 'border-[#0d9488]' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}"
                                        >
                                            <input type="radio" wire:model.live="answers.{{ $field->id }}" value="{{ $option->id }}" class="sr-only">
                                            <div class="absolute right-2 top-2 sm:right-3 sm:top-3 flex size-4 sm:size-5 items-center justify-center rounded-full border-2 {{ $checked ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                                @if ($checked) <div class="size-2 sm:size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                            </div>
                                            <div class="mb-2 sm:mb-3 flex size-10 sm:size-14 items-center justify-center rounded-full bg-[#e6f4f1]">
                                                <x-option-icon :name="$option->icon" class="size-6 sm:size-8 text-[#0d9488]" />
                                            </div>
                                            <span class="text-center text-[11px] sm:text-xs font-bold text-slate-700">{{ $option->tr('label') }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                {{-- Tombol teks: 3 kolom untuk pertanyaan pendek, 2 kolom untuk sisanya --}}
                                <div class="grid grid-cols-1 {{ $field->display_style === 'pill_compact' ? 'sm:grid-cols-3 gap-3' : 'sm:grid-cols-2 gap-3 sm:gap-4' }}">
                                    @foreach ($field->options as $option)
                                        @php($checked = ($answers[$field->id] ?? null) == $option->id)
                                        <label
                                            wire:key="option-{{ $option->id }}"
                                            class="relative flex cursor-pointer items-center justify-between rounded-lg border-2 {{ $field->display_style === 'pill_compact' ? 'p-3' : 'p-3 sm:p-4' }} transition-all {{ $checked ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}"
                                        >
                                            <input type="radio" wire:model.live="answers.{{ $field->id }}" value="{{ $option->id }}" class="sr-only">
                                            <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ $option->tr('label') }}</span>
                                            <div class="flex size-4 sm:size-5 items-center justify-center rounded-full border-2 {{ $checked ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                                @if ($checked) <div class="size-2 sm:size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </fieldset>
                    @endforeach
                @endif

                {{-- ==================== LANGKAH DATA DIRI ==================== --}}
                @if ($this->isPersonalStep)
                    <div class="border-b pb-5 border-slate-100 mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-[#0d9488]">{{ __('calculator.personal.title') }}</h2>
                        <p class="mt-1.5 text-sm text-slate-600">{{ __('calculator.personal.panel_description') }}</p>
                    </div>

                    {{-- Satu sumber kelas input: kotaknya harus terlihat (tanpa
                         `border` field-nya tampak seperti teks biasa), dan berubah
                         merah begitu fieldnya bermasalah. --}}
                    @php($inputBase = 'block w-full rounded-xl border bg-white py-3 text-sm transition focus:outline-none focus:ring-4')
                    @php($inputOk = 'border-slate-300 focus:border-[#0d9488] focus:ring-[#0d9488]/20')
                    @php($inputBad = 'border-red-400 bg-red-50/40 focus:border-red-500 focus:ring-red-100')

                    {{-- Row 1: Nama & Email --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 mb-5 sm:mb-6">
                        <fieldset>
                            <label for="name" class="block text-sm font-semibold text-slate-800 mb-2">{{ __('calculator.personal.name') }}</label>
                            <input
                                type="text" wire:model.blur="name" id="name" autocomplete="name"
                                placeholder="{{ __('calculator.personal.name_placeholder') }}"
                                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                                class="px-4 {{ $inputBase }} {{ $errors->has('name') ? $inputBad : $inputOk }}"
                            >
                            @error('name') <p id="name-error" class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </fieldset>

                        <fieldset>
                            <label for="email" class="block text-sm font-semibold text-slate-800 mb-2">{{ __('calculator.personal.email') }}</label>
                            <input
                                type="email" wire:model.blur="email" id="email" autocomplete="email" inputmode="email"
                                placeholder="{{ __('calculator.personal.email_placeholder') }}"
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                class="px-4 {{ $inputBase }} {{ $errors->has('email') ? $inputBad : $inputOk }}"
                            >
                            @error('email') <p id="email-error" class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </fieldset>
                    </div>

                    {{-- Row 2: WhatsApp & Tanggal Lahir --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 mb-5 sm:mb-6">
                        <fieldset>
                            <label for="whatsapp" class="block text-sm font-semibold text-slate-800 mb-2">{{ __('calculator.personal.whatsapp') }}</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">+62</span>
                                <input
                                    type="tel" wire:model.blur="whatsapp" id="whatsapp" autocomplete="tel-national" inputmode="numeric"
                                    placeholder="{{ __('calculator.personal.whatsapp_hint_placeholder') }}"
                                    @error('whatsapp') aria-invalid="true" aria-describedby="whatsapp-error" @enderror
                                    class="pl-14 pr-4 {{ $inputBase }} {{ $errors->has('whatsapp') ? $inputBad : $inputOk }}"
                                >
                            </div>
                            @error('whatsapp')
                                <p id="whatsapp-error" class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-slate-500">{{ __('calculator.personal.whatsapp_hint') }}</p>
                            @enderror
                        </fieldset>

                        <fieldset>
                            <label for="dob" class="block text-sm font-semibold text-slate-800 mb-2">{{ __('calculator.personal.dob') }}</label>
                            <input
                                type="date" wire:model.blur="dob" id="dob" autocomplete="bday"
                                max="{{ now()->subDay()->toDateString() }}"
                                @error('dob') aria-invalid="true" aria-describedby="dob-error" @enderror
                                class="px-4 text-slate-700 {{ $inputBase }} {{ $errors->has('dob') ? $inputBad : $inputOk }}"
                            >
                            @error('dob') <p id="dob-error" class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                        </fieldset>
                    </div>

                    {{-- Row 3: Jenis Kelamin --}}
                    <fieldset class="mb-5 sm:mb-6">
                        <legend class="text-sm font-semibold text-slate-800 mb-3">{{ __('calculator.personal.gender') }}</legend>
                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            @foreach (['male' => __('calculator.personal.gender_male'), 'female' => __('calculator.personal.gender_female')] as $value => $label)
                                <label class="relative flex cursor-pointer items-center justify-between gap-2 rounded-xl border-2 bg-white p-3 transition-all sm:p-4 {{ $gender === $value ? 'border-[#0d9488]' : 'border-slate-200 hover:border-[#0d9488]/50' }}">
                                    <input type="radio" wire:model.live="gender" value="{{ $value }}" class="sr-only">
                                    <span class="min-w-0 text-xs font-semibold text-slate-700 sm:text-sm">{{ $label }}</span>
                                    <div class="flex size-5 shrink-0 items-center justify-center rounded-full border-2 {{ $gender === $value ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if ($gender === $value) <div class="size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('gender') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </fieldset>

                    {{-- Row 4: Intent --}}
                    <fieldset class="mb-5 sm:mb-6">
                        <legend class="text-sm font-semibold text-slate-800 mb-3">{{ __('calculator.personal.intent') }}</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach (['belum_tahu', 'mungkin', 'tentu'] as $value)
                                <label class="relative flex cursor-pointer items-center justify-between gap-2 rounded-lg border-2 bg-white p-3 transition-all {{ $intent === $value ? 'border-[#0d9488]' : 'border-slate-200 hover:border-[#0d9488]/50' }}">
                                    <input type="radio" wire:model.live="intent" value="{{ $value }}" class="sr-only">
                                    <span class="min-w-0 text-xs font-semibold text-slate-700 sm:text-sm">{{ __('calculator.personal.intent_'.$value) }}</span>
                                    <div class="flex size-4 shrink-0 items-center justify-center rounded-full border-2 {{ $intent === $value ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if ($intent === $value) <div class="size-2 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('intent') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </fieldset>

                    {{-- Consent Checkbox --}}
                    <fieldset>
                        <div class="flex items-start gap-3 rounded-xl border bg-slate-50 p-3 sm:p-4 {{ $errors->has('consent') ? 'border-red-400 bg-red-50/60' : 'border-slate-200' }}">
                            <input id="consent" type="checkbox" wire:model.live="consent" class="size-5 mt-0.5 shrink-0 rounded border-slate-300 text-[#0d9488] focus:ring-[#0d9488]">
                            <label for="consent" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                {!! __('calculator.personal.consent', [
                                    'terms' => '<a href="#" class="font-semibold text-[#0d9488] hover:underline">'.e(__('calculator.personal.consent_terms')).'</a>',
                                    'privacy' => '<a href="#" class="font-semibold text-[#0d9488] hover:underline">'.e(__('calculator.personal.consent_privacy')).'</a>',
                                ]) !!}
                            </label>
                        </div>
                        @error('consent') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                    </fieldset>
                @endif

            </div>

            {{-- KOLOM KANAN: PANEL SKOR & SUMMARY (sticky hanya saat dua kolom;
                 di mobile panel ini ikut mengalir di bawah formulir) --}}
            <aside class="space-y-4 lg:sticky lg:top-24">

                {{-- Score Card --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                    <h3 class="text-center text-sm font-bold text-slate-900">{{ __('calculator.score_card.title') }}</h3>

                    {{-- Gauge Skor --}}
                    <div class="relative mx-auto mt-4 flex size-40 items-center justify-center">
                        <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="100, 100"/>
                            <path stroke="{{ $this->currentTier?->color ?? '#cbd5e1' }}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3" stroke-dasharray="{{ max($this->score, 2) }}, 100" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center flex flex-col items-center">
                            <span class="text-3xl font-bold text-slate-900">{{ $this->score }}</span>
                            <p class="text-[10px] font-medium text-slate-500">
                                {{ $this->currentTier?->tr('label') ?? __('calculator.score_card.empty') }}
                            </p>
                        </div>
                    </div>

                    {{-- Legenda Dampak --}}
                    <div class="mt-8 space-y-2 pt-4 border-t border-slate-100">
                        @foreach ($this->tiers as $tier)
                            <div class="flex items-center gap-2" wire:key="legend-{{ $tier->id }}">
                                <span class="size-2 rounded-full" style="background-color: {{ $tier->color }}"></span>
                                <span class="text-[10px] font-semibold text-slate-600">
                                    {{ $tier->tr('label') }} ({{ $tier->scoreRangeLabel() }})
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Ringkasan kategori yang sudah dilewati --}}
                @foreach ($this->summary as $block)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="summary-{{ $loop->index }}">
                        <h4 class="mb-4 text-xs font-bold text-slate-800">{{ $block['name'] }}</h4>
                        <div class="space-y-3 text-[11px] sm:text-xs">
                            {{-- Label dan jawabannya boleh membungkus: di layar sempit
                                 pasangan seperti "Konsumsi Daging Merah / Sedang (2-4x
                                 per minggu)" tidak muat dalam satu baris. --}}
                            @foreach ($block['rows'] as $row)
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 {{ $loop->last ? '' : 'border-b border-slate-100 pb-2' }}">
                                    <span class="text-slate-500">{{ $row['label'] }}</span>
                                    <span class="ml-auto text-right font-semibold text-slate-800">{{ $row['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

            </aside>
        </div>

        {{-- AREA TOMBOL NAVIGASI BAWAH --}}
        <div class="mt-8 rounded-xl border-t border-slate-200 bg-white p-4 shadow-sm sm:px-6">
            <div class="flex items-center justify-between gap-3">

                {{-- Tombol Kembali --}}
                @if ($this->step === 1)
                    <a href="{{ route('home') }}" wire:navigate class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:px-4 sm:text-sm">
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                        <span class="hidden sm:inline">{{ __('calculator.nav.back_home') }}</span>
                        <span class="sm:hidden">{{ __('calculator.nav.back') }}</span>
                    </a>
                @else
                    @php($previous = $this->categories->get($this->step - 2))
                    <button type="button" wire:click="previousStep" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:px-4 sm:text-sm">
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                        <span class="hidden sm:inline">{{ __('calculator.nav.back_to', ['step' => $previous?->tr('name') ?? __('calculator.personal.step_name')]) }}</span>
                        <span class="sm:hidden">{{ __('calculator.nav.back') }}</span>
                    </button>
                @endif

                {{-- Tombol Lanjut / Generate --}}
                @if ($this->isPersonalStep)
                    <button type="button" wire:click="submitLeads" wire:loading.attr="disabled" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg bg-[#0d9488] px-4 py-2 text-xs font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50 sm:px-5 sm:text-sm">
                        <span wire:loading.remove wire:target="submitLeads" class="hidden sm:inline">{{ __('calculator.nav.see_result') }}</span>
                        <span wire:loading.remove wire:target="submitLeads" class="sm:hidden">{{ __('calculator.nav.see_result_short') }}</span>
                        <span wire:loading wire:target="submitLeads">{{ __('calculator.nav.processing') }}</span>
                        <svg wire:loading.remove wire:target="submitLeads" class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                    </button>
                @else
                    @php($next = $this->categories->get($this->step))
                    <button type="button" wire:click="nextStep" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg bg-[#0d9488] px-4 py-2 text-xs font-semibold text-white transition hover:bg-teal-700 sm:px-5 sm:text-sm">
                        @if ($next)
                            <span class="hidden sm:inline">{{ __('calculator.nav.continue_to', ['step' => $next->tr('name')]) }}</span>
                            <span class="sm:hidden">{{ __('calculator.nav.continue') }}</span>
                        @else
                            <span class="hidden sm:inline">{{ __('calculator.nav.to_personal_data') }}</span>
                            <span class="sm:hidden">{{ __('calculator.nav.to_personal_data_short') }}</span>
                        @endif
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                    </button>
                @endif
            </div>
        </div>

    </main>

    <x-site-footer />
</div>
