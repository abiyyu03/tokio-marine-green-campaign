<?php

use App\Models\Leads;
use App\Models\Submission;
use App\Support\ResultReport;
use App\Support\ResultText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Detail satu peserta: data diri, hasil perhitungan, dan seluruh jawabannya.
 *
 * Angka hasilnya dibaca lewat App\Support\ResultReport — sumber yang sama
 * dengan halaman hasil peserta — supaya admin dan peserta tidak pernah melihat
 * angka yang berbeda.
 */
new #[Title('Detail Peserta | Admin')] class extends Component
{
    public string $uuid = '';

    /** Berisi uuid yang sedang dikonfirmasi penghapusannya, bukan sekadar bool. */
    public ?string $confirmingDelete = null;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;

        abort_if($this->submission === null, 404);
    }

    #[Computed]
    public function submission(): ?Submission
    {
        return Submission::query()
            ->where('uuid', $this->uuid)
            ->with([
                'lead',
                'result.tier.translations',
                'categoryResults.category.translations',
                'values.category.translations',
                'values.field.translations',
                'values.option.translations',
            ])
            ->first();
    }

    /**
     * ResultReport::forUuid() menyaring ke `completed` saja, jadi draft akan
     * mengembalikan null dan halaman ini ikut 404 — padahal draft justru salah
     * satu yang perlu dilihat admin. Konstruktornya publik dan submission-nya
     * sudah ter-eager-load, jadi dibungkus langsung tanpa query ulang.
     */
    #[Computed]
    public function report(): ?ResultReport
    {
        return $this->submission->result
            ? new ResultReport($this->submission)
            : null;
    }

    /**
     * Jawaban dikelompokkan per kategori, urut seperti langkah wizard.
     *
     * @return Collection<int, array{name: string, rows: Collection}>
     */
    #[Computed]
    public function answersByCategory(): Collection
    {
        return $this->submission->values
            ->filter(fn ($value) => $value->category !== null)
            ->sortBy(fn ($value) => [$value->category->sort_order, $value->field?->sort_order ?? 0])
            ->groupBy('emission_category_id')
            ->map(fn (Collection $rows) => [
                'name' => $rows->first()->category->tr('name'),
                'rows' => $rows,
            ])
            ->values();
    }

    #[Computed]
    public function timezone(): string
    {
        return config('carbon-calculator.display_timezone', 'UTC');
    }

    public function delete()
    {
        $submission = $this->submission;

        // Penjaga: payload /livewire/update yang dibuat manual tidak boleh bisa
        // melewati langkah konfirmasi.
        abort_if($submission === null || $this->confirmingDelete !== $submission->uuid, 403);

        $leadId = $submission->lead_id;
        $uuid = $submission->uuid;

        DB::transaction(function () use ($submission, $leadId) {
            // Submission dulu, lead belakangan. Kebalikannya berbahaya:
            // submissions.lead_id bersifat nullOnDelete, jadi menghapus lead
            // lebih dulu diam-diam mengosongkan lead_id submission LAIN milik
            // lead yang sama — barisnya tertinggal tanpa identitas.
            $submission->delete();   // cascade ke values, results, category results

            if ($leadId && ! Submission::query()->where('lead_id', $leadId)->exists()) {
                Leads::query()->whereKey($leadId)->delete();
            }
        });

        // Yang dicatat uuid-nya saja: log bukan tempat menyimpan data pribadi
        // yang barusan diminta untuk dihapus.
        Log::info('admin.submission.deleted', [
            'user_id' => auth()->id(),
            'submission_uuid' => $uuid,
            'lead_removed' => $leadId !== null,
        ]);

        session()->flash('status', __('admin.detail.deleted'));

        return $this->redirectRoute('admin.participants', navigate: true);
    }
};
?>

@php
    $submission = $this->submission;
    $lead = $submission->lead;
    $report = $this->report;
    $tier = $report?->tier();
    $zone = $this->timezone;
@endphp

<x-admin-shell
    :title="$lead?->name ?? __('admin.empty_value')"
    :subtitle="__('admin.status.'.$submission->status).' · '.$submission->created_at?->timezone($zone)->format('d/m/Y H:i')"
    :back="route('admin.participants')"
>
    <x-slot:actions>
        @if ($submission->status === 'completed')
            {{-- Halaman publik 404 untuk draft, jadi tautannya hanya muncul kalau sudah selesai. --}}
            <a href="{{ route('calculator.result', $submission->uuid) }}" target="_blank" rel="noopener"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                {{ __('admin.detail.view_result') }}
            </a>
            <a href="{{ route('calculator.report', ['uuid' => $submission->uuid, 'cetak' => 1]) }}" target="_blank" rel="noopener"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                {{ __('admin.detail.view_report') }}
            </a>
        @endif

        <button type="button" wire:click="$set('confirmingDelete', '{{ $submission->uuid }}')"
                class="rounded-lg border border-tm-red px-4 py-2 text-sm font-semibold text-tm-red transition hover:bg-red-50">
            {{ __('admin.detail.delete') }}
        </button>
    </x-slot:actions>

    {{-- Konfirmasi hapus, inline tanpa dialog JS supaya namanya ikut terbaca --}}
    @if ($confirmingDelete === $submission->uuid)
        <div class="mb-6 rounded-card border-2 border-tm-red bg-red-50 p-5">
            <h2 class="text-sm font-bold text-tm-red">
                {{ __('admin.detail.delete_confirm_title', ['name' => $lead?->name ?? $submission->uuid]) }}
            </h2>
            <p class="mt-2 text-sm text-slate-700">{{ __('admin.detail.delete_confirm_body') }}</p>
            <p class="mt-1 text-sm font-semibold text-tm-red">{{ __('admin.detail.delete_warning') }}</p>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="rounded-lg bg-tm-red px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60">
                    {{ __('admin.detail.delete_yes') }}
                </button>
                <button type="button" wire:click="$set('confirmingDelete', null)"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    {{ __('admin.detail.delete_cancel') }}
                </button>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">

            {{-- Data diri --}}
            <section class="rounded-card border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-bold text-navy-900">{{ __('admin.detail.identity') }}</h2>

                @if ($lead)
                    <dl class="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        @foreach ([
                            'email' => $lead->email,
                            'whatsapp' => $lead->whatsapp_number,
                            'dob' => $lead->dob?->format('d/m/Y'),
                            'gender' => $lead->gender ? __('admin.gender.'.$lead->gender) : null,
                            'intent' => $lead->intent ? __('admin.intent.'.$lead->intent) : null,
                            'locale' => strtoupper($lead->locale ?? ''),
                            'consented_at' => $lead->consented_at?->timezone($zone)->format('d/m/Y H:i'),
                            'consent_version' => $lead->consent_version,
                        ] as $key => $value)
                            <div>
                                <dt class="text-xs text-slate-500">{{ __('admin.columns.'.$key) }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-900 break-words">
                                    @if ($key === 'email' && $value)
                                        <a href="mailto:{{ $value }}" class="text-deep-700 hover:underline">{{ $value }}</a>
                                    @elseif ($key === 'whatsapp' && $value)
                                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $value) }}" target="_blank" rel="noopener" class="text-deep-700 hover:underline">{{ $value }}</a>
                                    @else
                                        {{ filled($value) ? $value : __('admin.empty_value') }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="mt-3 text-sm text-slate-500">{{ __('admin.detail.identity_empty') }}</p>
                @endif
            </section>

            {{-- Rincian per sektor --}}
            @if ($report)
                <section class="rounded-card border border-slate-200 bg-white p-6">
                    <h2 class="text-sm font-bold text-navy-900">{{ __('admin.detail.per_category') }}</h2>

                    <table class="mt-4 w-full text-sm">
                        <thead class="text-xs text-slate-500">
                            <tr>
                                <th scope="col" class="pb-2 text-left font-medium">{{ __('admin.columns.tier') }}</th>
                                <th scope="col" class="pb-2 text-right font-medium">{{ __('admin.detail.points') }}</th>
                                <th scope="col" class="pb-2 text-right font-medium">{{ __('admin.detail.percentage') }}</th>
                                <th scope="col" class="pb-2 text-right font-medium">{{ __('admin.columns.total') }}</th>
                                <th scope="col" class="pb-2 text-right font-medium">{{ __('admin.columns.raw_kg') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($report->categoryResults() as $row)
                                <tr wire:key="cat-{{ $row->emission_category_id }}">
                                    <th scope="row" class="py-2.5 text-left font-semibold text-slate-900">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="size-1.5 rounded-full" style="background-color: {{ $row->category->accent_color ?: '#94a3b8' }}"></span>
                                            {{ $row->category->tr('name') }}
                                        </span>
                                    </th>
                                    <td class="py-2.5 text-right text-slate-600 tabular-nums">{{ $row->score }}</td>
                                    <td class="py-2.5 text-right text-slate-600 tabular-nums">{{ ResultText::compact($row->percentage, 1) }}%</td>
                                    <td class="py-2.5 text-right font-semibold text-slate-900 tabular-nums">{{ ResultText::tonCompact($row->kg_co2e_year) }} Ton</td>
                                    <td class="py-2.5 text-right text-slate-400 tabular-nums">{{ ResultText::number($row->raw_kg_co2e_year ?? 0, 1) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <p class="mt-3 text-xs text-slate-400">{{ __('admin.detail.raw_hint') }}</p>
                </section>
            @endif

            {{-- Jawaban lengkap --}}
            <section class="rounded-card border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-bold text-navy-900">{{ __('admin.detail.answers') }}</h2>

                @if ($this->answersByCategory->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">{{ __('admin.detail.answers_empty') }}</p>
                @else
                    <div class="mt-4 space-y-6">
                        @foreach ($this->answersByCategory as $group)
                            <div wire:key="group-{{ $loop->index }}">
                                <h3 class="text-xs font-bold text-slate-500">{{ $group['name'] }}</h3>
                                <table class="mt-2 w-full text-sm">
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($group['rows'] as $value)
                                            <tr wire:key="value-{{ $value->id }}">
                                                <td class="py-2.5 pr-4 text-slate-600">{{ $value->field?->tr('label') ?? __('admin.empty_value') }}</td>
                                                <td class="py-2.5 pr-4 font-medium text-slate-900">
                                                    {{ $value->option?->tr('label') ?? $value->value_text ?? __('admin.empty_value') }}
                                                </td>
                                                <td class="w-16 py-2.5 text-right text-slate-500 tabular-nums">{{ $value->points }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- Sisi kanan: hasil & data teknis --}}
        <aside class="space-y-6">
            <section class="rounded-card border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-bold text-navy-900">{{ __('admin.detail.result') }}</h2>

                @if ($report)
                    <div class="mt-4 flex flex-col items-center">
                        <x-score-gauge :score="$report->score()" :tier="$tier" />

                        <p class="mt-4 text-2xl font-bold text-navy-900 tabular-nums">
                            {{ ResultText::tonCompact($report->totalKg()) }}
                            <span class="text-sm font-medium text-slate-500">Ton CO₂ / th</span>
                        </p>

                        @if ($tier)
                            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                                  style="color: {{ $tier->color }}; background-color: {{ $tier->color }}1A;">
                                {{ $tier->tr('badge_label') }}
                            </span>
                        @endif
                    </div>

                    <dl class="mt-6 space-y-3 border-t border-slate-100 pt-4 text-xs">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('admin.detail.document') }}</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">{{ $report->documentNumber() }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('admin.columns.raw_kg') }}</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">
                                {{ ResultText::number($submission->result->raw_kg_co2e_year ?? 0, 1) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">{{ __('admin.columns.completed_at') }}</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">
                                {{ $submission->completed_at?->timezone($zone)->format('d/m/Y H:i') ?? __('admin.empty_value') }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-3 text-sm text-slate-500">{{ __('admin.detail.result_empty') }}</p>
                @endif
            </section>

            <section class="rounded-card border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-bold text-navy-900">{{ __('admin.detail.technical') }}</h2>
                <dl class="mt-4 space-y-3 text-xs">
                    <div>
                        <dt class="text-slate-500">{{ __('admin.columns.uuid') }}</dt>
                        <dd class="mt-0.5 font-medium break-all text-slate-900">{{ $submission->uuid }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('admin.detail.ip') }}</dt>
                        <dd class="mt-0.5 font-medium text-slate-900">{{ $submission->ip_address ?? __('admin.empty_value') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">{{ __('admin.detail.user_agent') }}</dt>
                        <dd class="mt-0.5 leading-relaxed break-words text-slate-600">{{ $submission->user_agent ?? __('admin.empty_value') }}</dd>
                    </div>
                </dl>
            </section>
        </aside>
    </div>
</x-admin-shell>
