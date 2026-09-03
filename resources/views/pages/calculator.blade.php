<?php

use App\Services\CarbonCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Wizard perhitungan kalkulator karbon 4 Step (Transportasi, Listrik, Konsumsi, Data Diri).
 */
new #[Title('Hitung Jejak Karbonmu | Tokio Marine Green Campaign')] class extends Component
{
    public const DRAFT_CALC_KEY = 'calculator.draft_calc';

    public $step = 1;
    public $totalSteps = 4; // Sekarang totalnya 4 step (termasuk isi data diri)

    // --- Data Step 1: Transportasi ---
    public $mainTransport = null;
    public $distance = null;

    // --- Data Step 2: Listrik Rumah ---
    public $acUsage = null;
    public $fridgeType = null;
    public $powerLimit = null;

    // --- Data Step 3: Konsumsi & Sampah ---
    public $plasticUsage = null;
    public $shoppingBag = null;
    public $wasteSort = null;
    public $gallonWater = null;
    public $redMeat = null;
    public $onlineShopping = null;

    // --- Data Step 4: Leads / Data Diri ---
    public $name = '';
    public $email = '';
    public $whatsapp = '';
    public $dob = '';
    public $gender = ''; 
    public $intent = ''; 
    public $consent = false;

    // --- Skor sementara (Visual Dummy) ---
    public $score = 0;
    public $level = 'Dampak Ringan';

    public function mount()
    {
        $draft = session(self::DRAFT_CALC_KEY);
        if ($draft) {
            $this->mainTransport = $draft['mainTransport'] ?? null;
            $this->distance = $draft['distance'] ?? null;
            $this->acUsage = $draft['acUsage'] ?? null;
            $this->fridgeType = $draft['fridgeType'] ?? null;
            $this->powerLimit = $draft['powerLimit'] ?? null;
            $this->plasticUsage = $draft['plasticUsage'] ?? null;
            $this->shoppingBag = $draft['shoppingBag'] ?? null;
            $this->wasteSort = $draft['wasteSort'] ?? null;
            $this->gallonWater = $draft['gallonWater'] ?? null;
            $this->redMeat = $draft['redMeat'] ?? null;
            $this->onlineShopping = $draft['onlineShopping'] ?? null;
            $this->step = $draft['step'] ?? 1;
        }
    }

    public function updated()
    {
        session([self::DRAFT_CALC_KEY => $this->allInput()]);
        
        // Simulasi skor statis untuk tampilan visual sesuai step
        if ($this->step == 2) {
            $this->score = 25;
            $this->level = 'Dampak Ringan';
        } elseif ($this->step == 3) {
            $this->score = 46; 
            $this->level = 'Dampak Sedang';
        } elseif ($this->step == 4) {
            $this->score = 71; // Sesuai skor di gambar hasil
            $this->level = 'Dampak Tinggi';
        }
    }

    private function allInput()
    {
        return [
            'step' => $this->step,
            'mainTransport' => $this->mainTransport,
            'distance' => $this->distance,
            'acUsage' => $this->acUsage,
            'fridgeType' => $this->fridgeType,
            'powerLimit' => $this->powerLimit,
            'plasticUsage' => $this->plasticUsage,
            'shoppingBag' => $this->shoppingBag,
            'wasteSort' => $this->wasteSort,
            'gallonWater' => $this->gallonWater,
            'redMeat' => $this->redMeat,
            'onlineShopping' => $this->onlineShopping,
        ];
    }

    public function nextStep()
    {
        // Validasi per step sebelum pindah
        if ($this->step == 1) {
            $this->validate(['mainTransport' => 'required', 'distance' => 'required']);
        } elseif ($this->step == 2) {
            $this->validate(['acUsage' => 'required', 'fridgeType' => 'required', 'powerLimit' => 'required']);
        } elseif ($this->step == 3) {
            $this->validate([
                'plasticUsage' => 'required', 'shoppingBag' => 'required',
                'wasteSort' => 'required', 'gallonWater' => 'required',
                'redMeat' => 'required', 'onlineShopping' => 'required'
            ]);

            // Jika Step 3 lolos validasi, cek login
            if (Auth::check()) {
                // User sudah login, langsung generate hasil tanpa ke Step 4
                return redirect()->route('calculator.result', ['uuid' => 'dummy-uuid']);
            }
            // Jika belum login, biarkan lanjut ke Step 4
        }

        if ($this->step < $this->totalSteps) {
            $this->step++;
            $this->updated();
        }
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
            $this->updated();
        }
    }

    // Submit khusus untuk Step Terakhir (Data Diri)
    public function submitLeads()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'whatsapp' => 'required',
            'consent' => 'accepted',
        ]);

        // Disini panggil CarbonCalculator service lalu simpan ke DB..
        session()->forget(self::DRAFT_CALC_KEY);

        // Simulasi redirect ke halaman hasil (Ganti dengan UUID asli dari DB nantinya)
        return redirect()->route('calculator.result', ['uuid' => 'dummy-uuid']);
    }

    // Helper untuk menampilkan label di Summary Box
    public function getSummaryLabel($type, $value)
    {
        $labels = [
            'transport' => [
                'mobil_bensin' => 'Mobil Bensin', 'motor_bensin' => 'Motor Bensin',
                'mobil_listrik' => 'Mobil Listrik (EV)', 'motor_listrik' => 'Motor Listrik (EV)',
                'umum' => 'Transportasi Umum', 'kombinasi' => 'Kombinasi Transportasi'
            ],
            'distance' => [
                'less_10' => '< 10 km / hari', '10_25' => '10 – 25 km / hari',
                '26_50' => '26 – 50 km / hari', 'more_50' => '> 50 km / hari'
            ],
            'ac' => [
                'ac_none' => 'Tidak Menggunakan AC', 
                'ac_1_less_5_std' => '1 Unit (< 5 jam / hari) - Standar',
                'ac_1_more_8_std' => '1 Unit (> 8 jam / hari) - Standar',
                'ac_1_less_5_inv' => '1 Unit (< 5 jam / hari) - Inverter',
                'ac_1_more_8_inv' => '1 Unit (> 8 jam / hari) - Inverter',
                'ac_more_1' => 'Lebih dari 1 Unit AC'
            ],
            'fridge' => [
                'fridge_none' => 'Tidak Ada Kulkas', 
                'fridge_std' => 'Kulkas Standar (Non-Inverter)',
                'fridge_inv' => 'Kulkas Hemat Energi (Inverter)'
            ],
            'power' => [
                'va_900' => '≤ 900 VA', 'va_1300' => '1300 VA', 'va_2200' => '2200 VA'
            ]
        ];
        return $labels[$type][$value] ?? '-';
    }
};
?>

<div class="flex min-h-screen flex-col bg-[#f4f7f9]">
    <x-site-header active="calculator" />

    {{-- HEADER ATAS WIZARD --}}
    <div class="bg-white w-full border-b border-slate-200">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#1e293b]">Kalkulator Karbon</h1>

            {{-- Progress Bar --}}
            <div class="mt-6 w-full lg:w-3/4">
                @php
                    if ($step == 1) {
                        $progress = 30;
                    } elseif ($step == 2) {
                        $progress = 60;
                    } elseif ($step == 3) {
                        $progress = 90;
                    } else {
                        $progress = 99;
                    }
                @endphp
                <div class="relative h-2 w-full rounded-full bg-slate-200">
                    {{-- Bar hijau yang berjalan --}}
                    <div class="absolute left-0 top-0 h-2 rounded-full bg-[#0d9488] transition-all duration-500" style="width: {{ $progress }}%"></div>
                    
                    {{-- Titik Penanda (Disesuaikan dengan persentase absolut) --}}
                    <div class="absolute inset-0">
                        {{-- Titik 0% (Start) --}}
                        <span class="absolute top-1/2 -translate-y-1/2 size-1.5 rounded-full {{ $progress >= 0 ? 'bg-white ring-2 ring-[#0d9488]' : 'bg-slate-300' }}" style="left: 0%;"></span>
                        
                        {{-- Titik 30% (Step 1) --}}
                        <span class="absolute top-1/2 -translate-y-1/2 size-1.5 rounded-full {{ $progress >= 30 ? 'bg-white ring-2 ring-[#0d9488]' : 'bg-slate-300' }}" style="left: 30%;"></span>
                        
                        {{-- Titik 60% (Step 2) --}}
                        <span class="absolute top-1/2 -translate-y-1/2 size-1.5 rounded-full {{ $progress >= 60 ? 'bg-white ring-2 ring-[#0d9488]' : 'bg-slate-300' }}" style="left: 60%;"></span>
                        
                        {{-- Titik 90% (Step 3) --}}
                        <span class="absolute top-1/2 -translate-y-1/2 size-1.5 rounded-full {{ $progress >= 90 ? 'bg-white ring-2 ring-[#0d9488]' : 'bg-slate-300' }}" style="left: 90%;"></span>
                        
                        {{-- Titik 99% (Data Diri) --}}
                        <span class="absolute top-1/2 -translate-y-1/2 size-1.5 rounded-full {{ $progress >= 99 ? 'bg-white ring-2 ring-[#0d9488]' : 'bg-slate-300' }}" style="left: 99%;"></span>
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
        
        {{-- CARD INDIKATOR (Hanya Tampil di Step 1-3) --}}
        @if ($step < 4)
        <div class="mb-8 overflow-hidden rounded-2xl bg-white px-4 py-8 shadow-sm sm:px-16 sm:py-10">
            <div class="relative mx-auto flex max-w-4xl items-start justify-between">
                
                {{-- Garis Penghubung --}}
                <div class="absolute left-10 right-10 top-7 h-[2px] bg-slate-200 sm:left-16 sm:right-16 sm:top-8"></div>
                <div class="absolute left-10 top-7 h-[2px] bg-[#0d9488] transition-all duration-500 sm:left-16 sm:top-8" 
                     style="width: {{ $step === 1 ? '0%' : ($step === 2 ? '50%' : '100%') }}; max-width: calc(100% - 2.5rem);">
                </div>

                {{-- Step 1 Indicator --}}
                <div class="relative z-10 flex w-20 flex-col items-center gap-2 sm:w-32 sm:gap-3">
                    <div class="flex size-14 items-center justify-center rounded-full border-[3px] border-[#0d9488] bg-white p-1 transition-all sm:size-16">
                        <div class="flex size-full items-center justify-center rounded-full {{ $step > 1 ? 'bg-white border-2 border-[#0d9488]' : 'bg-[#0d9488]' }}">
                            <svg class="size-5 sm:size-6 {{ $step > 1 ? 'text-[#0d9488]' : 'text-white' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H8.5a1 1 0 00-.8.4L5 11l-5.16.86a1 1 0 00-.84.99V16h3m12 0a2 2 0 100 4 2 2 0 000-4zm-12 0a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                        </div>
                    </div>
                    <span class="text-center text-[10px] font-bold leading-tight text-[#0d9488] sm:text-sm sm:leading-snug">Transportasi<br>darat</span>
                </div>

                {{-- Step 2 Indicator --}}
                <div class="relative z-10 flex w-20 flex-col items-center gap-2 sm:w-32 sm:gap-3">
                    <div class="flex size-14 items-center justify-center rounded-full {{ $step >= 2 ? 'border-[3px] border-[#0d9488] bg-white p-1' : 'bg-[#e2e8f0]' }} transition-all sm:size-16">
                        <div class="flex size-full items-center justify-center rounded-full {{ $step > 2 ? 'bg-white border-2 border-[#0d9488]' : ($step == 2 ? 'bg-[#0d9488]' : 'bg-transparent') }}">
                            <svg class="size-5 sm:size-6 {{ $step > 2 ? 'text-[#0d9488]' : ($step == 2 ? 'text-white' : 'text-slate-400') }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                        </div>
                    </div>
                    <span class="text-center text-[10px] leading-tight {{ $step >= 2 ? 'font-bold text-[#0d9488]' : 'font-medium text-slate-500' }} sm:text-sm sm:leading-snug">Listrik Rumah</span>
                </div>

                {{-- Step 3 Indicator --}}
                <div class="relative z-10 flex w-20 flex-col items-center gap-2 sm:w-32 sm:gap-3">
                    <div class="flex size-14 items-center justify-center rounded-full {{ $step >= 3 ? 'border-[3px] border-[#0d9488] bg-white p-1' : 'bg-[#e2e8f0]' }} transition-all sm:size-16">
                        <div class="flex size-full items-center justify-center rounded-full {{ $step >= 3 ? 'bg-[#0d9488]' : 'bg-transparent' }}">
                            <svg class="size-5 sm:size-6 {{ $step >= 3 ? 'text-white' : 'text-slate-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 8V11m0-5.5v-1a1.5 1.5 0 113 0v1m0 4V11"/>
                            </svg>
                        </div>
                    </div>
                    <span class="text-center text-[10px] leading-tight {{ $step >= 3 ? 'font-bold text-[#0d9488]' : 'font-medium text-slate-500' }} sm:text-sm sm:leading-snug">Konsumsi &<br>Sampah</span>
                </div>
            </div>
        </div>
        @endif

        {{-- LAYOUT UTAMA --}}
        <div class="grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">
            
            {{-- KOLOM KIRI: FORMULIR WIZARD --}}
            <div class="{{ $step == 4 ? 'rounded-2xl bg-white p-6 shadow-sm border border-slate-100' : 'space-y-6' }}">
                
                @if ($errors->any())
                    <div class="rounded-lg bg-red-50 p-3 text-sm text-red-600 border border-red-200 mb-6">
                        Mohon lengkapi data yang masih kosong sebelum melanjutkan.
                    </div>
                @endif

                {{-- ==================== STEP 1 ==================== --}}
                @if ($step == 1)
                    <h2 class="text-xl sm:text-2xl font-bold text-[#0d9488] mb-2 border-b pb-4 border-slate-200">Transportasi</h2>

                    <fieldset class="space-y-4">
                        <legend class="text-sm sm:text-base font-bold text-slate-800 mb-4">Apa moda transportasi utama yang kamu gunakan sehari-hari?</legend>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                            @php
                                $transportOptions = [
                                    ['id' => 'mobil_bensin', 'label' => 'Mobil Bensin (BBM)', 'svg' => '<path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H8.5a1 1 0 00-.8.4L5 11l-5.16.86a1 1 0 00-.84.99V16h3m12 0a2 2 0 100 4 2 2 0 000-4zm-12 0a2 2 0 100 4 2 2 0 000-4z"/>'],
                                    ['id' => 'motor_bensin', 'label' => 'Motor Bensin (BBM)', 'svg' => '<circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/><path d="M14 17h-4m-3.5-2.5L10 8h4l1.5 3H20l-1.5 3H17"/>'],
                                    ['id' => 'mobil_listrik', 'label' => 'Mobil Listrik (EV)', 'svg' => '<path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H8.5a1 1 0 00-.8.4L5 11l-5.16.86a1 1 0 00-.84.99V16h3m12 0a2 2 0 100 4 2 2 0 000-4zm-12 0a2 2 0 100 4 2 2 0 000-4z"/><path d="M3 10v4h2"/><path d="M5 12h2l1-2"/>'],
                                    ['id' => 'motor_listrik', 'label' => 'Motor Listrik (EV)', 'svg' => '<circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/><path d="M14 17h-4m-3.5-2.5L10 8h4l1.5 3H20l-1.5 3H17"/><path d="M3 14v-2h2l1-2"/>'],
                                    ['id' => 'umum', 'label' => 'Transportasi Umum', 'svg' => '<path d="M4 10h16M4 14h16m-2 4H6a2 2 0 01-2-2V8a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2z"/><path d="M8 22L6 18M16 22l2-4"/>'],
                                    ['id' => 'kombinasi', 'label' => 'Kombinasi Transportasi', 'svg' => '<path d="M4 8h10M4 12h10m4-4h2M18 12h2M6 16h12"/>'],
                                ];
                            @endphp
                            @foreach($transportOptions as $opt)
                                <label class="relative flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 p-3 sm:p-4 transition-all {{ $mainTransport === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                    <input type="radio" wire:model.live="mainTransport" value="{{ $opt['id'] }}" class="sr-only">
                                    <div class="absolute right-2 top-2 sm:right-3 sm:top-3 flex size-4 sm:size-5 items-center justify-center rounded-full border-2 {{ $mainTransport === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if($mainTransport === $opt['id']) <div class="size-2 sm:size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                    <div class="mb-2 sm:mb-3 flex size-10 sm:size-14 items-center justify-center rounded-full bg-[#e6f4f1]">
                                        <svg class="size-6 sm:size-8 text-[#0d9488]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $opt['svg'] !!}</svg>
                                    </div>
                                    <span class="text-center text-[11px] sm:text-xs font-bold text-slate-700">{{ $opt['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="pt-6 space-y-4">
                        <legend class="text-sm sm:text-base font-bold text-slate-800 mb-4">Berapa estimasi total jarak yang kamu tempuh dalam sehari?</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            @php
                                $distOptions = [
                                    ['id' => 'less_10', 'label' => 'Kurang dari 10 km / hari'],
                                    ['id' => '10_25', 'label' => '10 – 25 km / hari'],
                                    ['id' => '26_50', 'label' => '26 – 50 km / hari'],
                                    ['id' => 'more_50', 'label' => 'Lebih dari 50 km / hari'],
                                ];
                            @endphp
                            @foreach($distOptions as $opt)
                                <label class="relative flex cursor-pointer items-center justify-between rounded-lg border-2 p-3 sm:p-4 transition-all {{ $distance === $opt['id'] ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                    <input type="radio" wire:model.live="distance" value="{{ $opt['id'] }}" class="sr-only">
                                    <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ $opt['label'] }}</span>
                                    <div class="flex size-4 sm:size-5 items-center justify-center rounded-full border-2 {{ $distance === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if($distance === $opt['id']) <div class="size-2 sm:size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif


                {{-- ==================== STEP 2 ==================== --}}
                @if ($step == 2)
                    <h2 class="text-xl sm:text-2xl font-bold text-[#0d9488] mb-2 border-b pb-4 border-slate-200">Listrik Rumah</h2>

                    <fieldset class="space-y-4">
                        <legend class="text-sm sm:text-base font-bold text-slate-800 mb-4">Bagaimana penggunaan Air Conditioner (AC) di rumahmu?</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            @php
                                $acOptions = [
                                    ['id' => 'ac_none', 'label' => 'Tidak Menggunakan AC'],
                                    ['id' => 'ac_1_less_5_std', 'label' => '1 Unit (< 5 jam / hari) - Standar'],
                                    ['id' => 'ac_1_more_8_std', 'label' => '1 Unit (> 8 jam / hari) - Standar'],
                                    ['id' => 'ac_1_less_5_inv', 'label' => '1 Unit (< 5 jam / hari) - Inverter'],
                                    ['id' => 'ac_1_more_8_inv', 'label' => '1 Unit (> 8 jam / hari) - Inverter'],
                                    ['id' => 'ac_more_1', 'label' => 'Lebih dari 1 Unit AC'],
                                ];
                            @endphp
                            @foreach($acOptions as $opt)
                                <label class="relative flex cursor-pointer items-center justify-between rounded-lg border-2 p-3 sm:p-4 transition-all {{ $acUsage === $opt['id'] ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                    <input type="radio" wire:model.live="acUsage" value="{{ $opt['id'] }}" class="sr-only">
                                    <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ $opt['label'] }}</span>
                                    <div class="flex size-4 sm:size-5 items-center justify-center rounded-full border-2 {{ $acUsage === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if($acUsage === $opt['id']) <div class="size-2 sm:size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="pt-6 space-y-4">
                        <legend class="text-sm sm:text-base font-bold text-slate-800 mb-4">Tipe kulkas apa yang digunakan di rumahmu?</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            @php
                                $fridgeOptions = [
                                    ['id' => 'fridge_none', 'label' => 'Tidak Ada Kulkas'],
                                    ['id' => 'fridge_std', 'label' => 'Kulkas Standar (Non-Inverter)'],
                                    ['id' => 'fridge_inv', 'label' => 'Kulkas Hemat Energi (Inverter)'],
                                ];
                            @endphp
                            @foreach($fridgeOptions as $opt)
                                <label class="relative flex cursor-pointer items-center justify-between rounded-lg border-2 p-3 sm:p-4 transition-all {{ $fridgeType === $opt['id'] ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                    <input type="radio" wire:model.live="fridgeType" value="{{ $opt['id'] }}" class="sr-only">
                                    <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ $opt['label'] }}</span>
                                    <div class="flex size-4 sm:size-5 items-center justify-center rounded-full border-2 {{ $fridgeType === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if($fridgeType === $opt['id']) <div class="size-2 sm:size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="pt-6 space-y-4">
                        <legend class="text-sm sm:text-base font-bold text-slate-800 mb-4">Berapa batas daya listrik (VA) terpasang di rumahmu?</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            @php
                                $powerOptions = [
                                    ['id' => 'va_900', 'label' => '≤ 900 VA'],
                                    ['id' => 'va_1300', 'label' => '1300 VA'],
                                    ['id' => 'va_2200', 'label' => '2200 VA'],
                                ];
                            @endphp
                            @foreach($powerOptions as $opt)
                                <label class="relative flex cursor-pointer items-center justify-between rounded-lg border-2 p-3 sm:p-4 transition-all {{ $powerLimit === $opt['id'] ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                    <input type="radio" wire:model.live="powerLimit" value="{{ $opt['id'] }}" class="sr-only">
                                    <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ $opt['label'] }}</span>
                                    <div class="flex size-4 sm:size-5 items-center justify-center rounded-full border-2 {{ $powerLimit === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if($powerLimit === $opt['id']) <div class="size-2 sm:size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif


                {{-- ==================== STEP 3 ==================== --}}
                @if ($step == 3)
                    <h2 class="text-xl sm:text-2xl font-bold text-[#0d9488] mb-2 border-b pb-4 border-slate-200">Konsumsi & Sampah</h2>

                    {{-- Pertanyaan 3 (Singkat untuk efisiensi baris) --}}
                    @foreach([
                        ['model'=>'plasticUsage', 'label'=>'Seberapa sering kamu menggunakan plastik sekali pakai?', 'ops'=>[['id'=>'jarang', 'label'=>'Jarang (0-2x / minggu)'], ['id'=>'sedang', 'label'=>'Sedang (3-5x / minggu)'], ['id'=>'sering', 'label'=>'Sering (>5x / minggu)']]],
                        ['model'=>'shoppingBag', 'label'=>'Apakah kamu selalu membawa tas belanja sendiri saat bepergian?', 'ops'=>[['id'=>'selalu', 'label'=>'Ya, Selalu'], ['id'=>'kadang', 'label'=>'Kadang-kadang'], ['id'=>'tidak', 'label'=>'Tidak Pernah']]],
                        ['model'=>'wasteSort', 'label'=>'Apakah kamu memilah sampah organik dan anorganik di rumah?', 'ops'=>[['id'=>'ya', 'label'=>'Ya'], ['id'=>'tidak', 'label'=>'Tidak']]],
                        ['model'=>'gallonWater', 'label'=>'Apakah kamu menggunakan air galon isi ulang untuk kebutuhan minum?', 'ops'=>[['id'=>'ya', 'label'=>'Ya'], ['id'=>'tidak', 'label'=>'Tidak']]],
                        ['model'=>'redMeat', 'label'=>'Seberapa sering kamu mengonsumsi daging merah (sapi/kambing)?', 'ops'=>[['id'=>'jarang', 'label'=>'Jarang (0-1x / minggu)'], ['id'=>'sedang', 'label'=>'Sedang (2-4x / minggu)'], ['id'=>'sering', 'label'=>'Sering (>5x / minggu)']]],
                        ['model'=>'onlineShopping', 'label'=>'Berapa frekuensi kamu melakukan transaksi belanja online dalam sebulan?', 'ops'=>[['id'=>'less_5', 'label'=>'≤ 5 kali / bulan'], ['id'=>'more_5', 'label'=>'> 5 kali / bulan']]]
                    ] as $index => $q)
                        <fieldset class="{{ $index > 0 ? 'pt-6' : 'pt-2' }} space-y-4">
                            <legend class="text-sm sm:text-base font-bold text-slate-800 mb-3">{{ $q['label'] }}</legend>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @foreach($q['ops'] as $opt)
                                <label class="relative flex cursor-pointer items-center justify-between rounded-lg border-2 p-3 transition-all {{ ${$q['model']} === $opt['id'] ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                    <input type="radio" wire:model.live="{{ $q['model'] }}" value="{{ $opt['id'] }}" class="sr-only">
                                    <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ $opt['label'] }}</span>
                                    <div class="flex size-4 items-center justify-center rounded-full border-2 {{ ${$q['model']} === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                        @if(${$q['model']} === $opt['id']) <div class="size-2 rounded-full bg-[#0d9488]"></div> @endif
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                @endif


                {{-- ==================== STEP 4: DATA DIRI ==================== --}}
                @if ($step == 4)
                    <div class="border-b pb-5 border-slate-100 mb-6">
                        <h2 class="text-xl sm:text-2xl font-bold text-[#0d9488]">Isi Data Diri</h2>
                        <p class="mt-1.5 text-sm text-slate-600">Satu langkah lagi untuk melihat laporan jejak karbonmu dan berkontribusi untuk bumi.</p>
                    </div>

                    {{-- Row 1: Nama & Email --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <fieldset>
                            <label for="name" class="block text-sm font-semibold text-slate-800 mb-2">Nama Lengkap</label>
                            <input type="text" wire:model="name" id="name" placeholder="Contoh: Andi Pratama" class="block w-full rounded-xl border-slate-200 px-4 py-3 text-sm focus:border-[#0d9488] focus:ring-[#0d9488]/20">
                            @error('name') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                        </fieldset>

                        <fieldset>
                            <label for="email" class="block text-sm font-semibold text-slate-800 mb-2">Alamat Email aktif</label>
                            <input type="email" wire:model="email" id="email" placeholder="contoh@email.com" class="block w-full rounded-xl border-slate-200 px-4 py-3 text-sm focus:border-[#0d9488] focus:ring-[#0d9488]/20">
                            @error('email') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                        </fieldset>
                    </div>

                    {{-- Row 2: WhatsApp & Tanggal Lahir --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <fieldset>
                            <label for="whatsapp" class="block text-sm font-semibold text-slate-800 mb-2">Nomor WhatsApp</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-500 font-medium">+62</span>
                                <input type="text" wire:model="whatsapp" id="whatsapp" placeholder="8123456789" class="block w-full rounded-xl border-slate-200 pl-14 pr-4 py-3 text-sm focus:border-[#0d9488] focus:ring-[#0d9488]/20">
                            </div>
                            @error('whatsapp') <span class="text-xs text-red-600 mt-1">{{ $message }}</span> @enderror
                        </fieldset>

                        <fieldset>
                            <label for="dob" class="block text-sm font-semibold text-slate-800 mb-2">Tanggal Lahir</label>
                            <input type="date" wire:model="dob" id="dob" class="block w-full rounded-xl border-slate-200 px-4 py-3 text-sm focus:border-[#0d9488] focus:ring-[#0d9488]/20 text-slate-700">
                        </fieldset>
                    </div>

                    {{-- Row 3: Jenis Kelamin --}}
                    <fieldset class="mb-6">
                        <legend class="text-sm font-semibold text-slate-800 mb-3">Jenis Kelamin</legend>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex cursor-pointer items-center justify-between rounded-xl border-2 p-4 transition-all {{ $gender === 'male' ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                <input type="radio" wire:model.live="gender" value="male" class="sr-only">
                                <span class="text-sm font-semibold text-slate-700">Laki-laki</span>
                                <div class="flex size-5 items-center justify-center rounded-full border-2 {{ $gender === 'male' ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                    @if($gender === 'male') <div class="size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                </div>
                            </label>
                            <label class="relative flex cursor-pointer items-center justify-between rounded-xl border-2 p-4 transition-all {{ $gender === 'female' ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                <input type="radio" wire:model.live="gender" value="female" class="sr-only">
                                <span class="text-sm font-semibold text-slate-700">Perempuan</span>
                                <div class="flex size-5 items-center justify-center rounded-full border-2 {{ $gender === 'female' ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                    @if($gender === 'female') <div class="size-2.5 rounded-full bg-[#0d9488]"></div> @endif
                                </div>
                            </label>
                        </div>
                    </fieldset>

                    {{-- Row 4: Intent --}}
                    <fieldset class="mb-6">
                        <legend class="text-sm font-semibold text-slate-800 mb-3">Apakah kamu berencana untuk mengurangi emisi karbonmu setelah melihat hasil ini?</legend>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach([['id'=>'belum_tahu', 'label'=>'Belum Tahu'], ['id'=>'mungkin', 'label'=>'Mungkin'], ['id'=>'tentu', 'label'=>'Tentu, Pasti']] as $opt)
                            <label class="relative flex cursor-pointer items-center justify-between rounded-lg border-2 p-3 transition-all {{ $intent === $opt['id'] ? 'border-[#0d9488] bg-white' : 'border-slate-200 hover:border-[#0d9488]/50 bg-white' }}">
                                <input type="radio" wire:model.live="intent" value="{{ $opt['id'] }}" class="sr-only">
                                <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ $opt['label'] }}</span>
                                <div class="flex size-4 items-center justify-center rounded-full border-2 {{ $intent === $opt['id'] ? 'border-[#0d9488]' : 'border-slate-300' }}">
                                    @if($intent === $opt['id']) <div class="size-2 rounded-full bg-[#0d9488]"></div> @endif
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </fieldset>

                    {{-- Consent Checkbox --}}
                    <fieldset>
                        <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <input id="consent" type="checkbox" wire:model="consent" class="size-5 mt-0.5 rounded border-slate-300 text-[#0d9488] focus:ring-[#0d9488]">
                            <label for="consent" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                Saya menyetujui <a href="#" class="font-semibold text-[#0d9488] hover:underline">Syarat & Ketentuan</a> serta <a href="#" class="font-semibold text-[#0d9488] hover:underline">Kebijakan Privasi</a> yang berlaku dalam kampanye Tokio Marine Green Campaign ini.
                            </label>
                        </div>
                        @error('consent') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                    </fieldset>
                @endif

            </div>

            {{-- KOLOM KANAN: PANEL SKOR & SUMMARY (Sticky) --}}
            <aside class="sticky top-24 space-y-4">
                
                {{-- Score Card --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                    <h3 class="text-center text-sm font-bold text-slate-900">Skor Kamu</h3>

                    {{-- Gauge Skor --}}
                    <div class="relative mx-auto mt-4 flex size-40 items-center justify-center">
                        <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                            <path class="text-[#fce7f3]" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="100, 100"/>
                            @php
                                $ringColor = $score > 60 ? '#ef4444' : ($score > 30 ? '#f59e0b' : '#059669');
                            @endphp
                            <path stroke="{{ $ringColor }}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3" stroke-dasharray="{{ max($score, 2) }}, 100" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center flex flex-col items-center">
                            <span class="text-3xl font-bold text-slate-900">{{ $score }}</span>
                            <p class="text-[10px] font-medium text-slate-500">{{ $level }}</p>
                        </div>
                    </div>

                    {{-- Legenda Dampak --}}
                    <div class="mt-8 space-y-2 pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-[#059669]"></span>
                            <span class="text-[10px] font-semibold text-slate-600">Dampak Ringan (0-30)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-[#f59e0b]"></span>
                            <span class="text-[10px] font-semibold text-slate-600">Dampak Sedang (31-60)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-[#ef4444]"></span>
                            <span class="text-[10px] font-semibold text-slate-600">Dampak Tinggi (61-100)</span>
                        </div>
                    </div>
                </div>

                {{-- Summary Transportasi (Tampil Mulai Step 2) --}}
                @if ($step >= 2 && $mainTransport && $distance)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h4 class="mb-4 text-xs font-bold text-slate-800">Transportasi Darat</h4>
                    <div class="space-y-3 text-[11px] sm:text-xs">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                            <span class="text-slate-500">Moda</span>
                            <span class="font-semibold text-slate-800 text-right">{{ $this->getSummaryLabel('transport', $mainTransport) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Jarak</span>
                            <span class="font-semibold text-slate-800 text-right">{{ $this->getSummaryLabel('distance', $distance) }}</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Summary Listrik (Tampil Mulai Step 3) --}}
                @if ($step >= 3 && $acUsage && $fridgeType && $powerLimit)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h4 class="mb-4 text-xs font-bold text-slate-800">Listrik Rumah</h4>
                    <div class="space-y-3 text-[11px] sm:text-xs">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-2 gap-2">
                            <span class="text-slate-500 whitespace-nowrap">AC</span>
                            <span class="font-semibold text-slate-800 text-right">{{ $this->getSummaryLabel('ac', $acUsage) }}</span>
                        </div>
                        <div class="flex justify-between items-start border-b border-slate-100 pb-2 gap-2">
                            <span class="text-slate-500 whitespace-nowrap">Kulkas</span>
                            <span class="font-semibold text-slate-800 text-right">{{ $this->getSummaryLabel('fridge', $fridgeType) }}</span>
                        </div>
                        <div class="flex justify-between items-start gap-2">
                            <span class="text-slate-500 whitespace-nowrap">Daya Listrik</span>
                            <span class="font-semibold text-slate-800 text-right">{{ $this->getSummaryLabel('power', $powerLimit) }}</span>
                        </div>
                    </div>
                </div>
                @endif

            </aside>
        </div>
        
        {{-- AREA TOMBOL NAVIGASI BAWAH --}}
        <div class="mt-8 border-t border-slate-200 bg-white p-4 shadow-sm sm:rounded-xl sm:px-6">
            <div class="flex items-center justify-between">
                
                {{-- Tombol Kembali --}}
                @if ($step == 1)
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                        <span class="hidden sm:inline">Kembali ke Beranda</span>
                        <span class="sm:hidden">Kembali</span>
                    </a>
                @else
                    <button type="button" wire:click="previousStep" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                        @if ($step == 2)
                            <span class="hidden sm:inline">Kembali ke Transportasi Darat</span>
                            <span class="sm:hidden">Kembali</span>
                        @elseif ($step == 3)
                            <span class="hidden sm:inline">Kembali ke Listrik Rumah</span>
                            <span class="sm:hidden">Kembali</span>
                        @elseif ($step == 4)
                            <span class="hidden sm:inline">Kembali ke Konsumsi & Sampah</span>
                            <span class="sm:hidden">Kembali</span>
                        @endif
                    </button>
                @endif

                {{-- Tombol Lanjut / Generate --}}
                @if ($step == 1)
                    <button type="button" wire:click="nextStep" class="inline-flex items-center gap-2 rounded-lg bg-[#0d9488] px-5 py-2 text-xs sm:text-sm font-semibold text-white transition hover:bg-teal-700">
                        Lanjut <span class="hidden sm:inline">ke Listrik Rumah</span>
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                    </button>
                @elseif ($step == 2)
                    <button type="button" wire:click="nextStep" class="inline-flex items-center gap-2 rounded-lg bg-[#0d9488] px-5 py-2 text-xs sm:text-sm font-semibold text-white transition hover:bg-teal-700">
                        Lanjut <span class="hidden sm:inline">ke Konsumsi & Sampah</span>
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                    </button>
                @elseif ($step == 3)
                    <button type="button" wire:click="nextStep" class="inline-flex items-center gap-2 rounded-lg bg-[#0d9488] px-5 py-2 text-xs sm:text-sm font-semibold text-white transition hover:bg-teal-700">
                        Lanjut ke Isi Data Diri
                        <svg class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                    </button>
                @elseif ($step == 4)
                    <button type="button" wire:click="submitLeads" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg bg-[#0d9488] px-5 py-2 text-xs sm:text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50">
                        <span wire:loading.remove>Generate Laporan</span>
                        <span wire:loading>Processing...</span>
                        <svg wire:loading.remove class="size-4 sm:size-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 0-1.06L10.94 10 7.21 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.24a.75.75 0 0 1 0 1.06l-4.25 4.24a.75.75 0 0 1-1.06 0Z" clip-rule="evenodd" /></svg>
                    </button>
                @endif
            </div>
        </div>

    </main>

    <x-site-footer />
</div>