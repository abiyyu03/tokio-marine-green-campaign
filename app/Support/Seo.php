<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Metadata satu halaman untuk mesin pencari dan pratinjau tautan.
 *
 * Dipilih berdasarkan nama route, bukan diteruskan dari tiap komponen: halaman
 * di aplikasi ini semuanya Livewire SFC yang hanya bisa mengoper judul lewat
 * atribut #[Title] (nilainya harus konstan, jadi tidak bisa diterjemahkan).
 * Dengan cara ini judul, deskripsi, dan kartu WhatsApp punya satu sumber yang
 * ikut berganti bahasa — teksnya di lang/{locale}/seo.php.
 */
class Seo
{
    /** Nama route => kunci blok teks di lang/{locale}/seo.php. */
    private const PAGES = [
        'home' => 'home',
        'calculator' => 'calculator',
        'calculator.result' => 'result',
        'calculator.report' => 'report',
    ];

    /**
     * Halaman yang tidak boleh masuk indeks: area admin, serta halaman hasil
     * dan laporan yang beralamat uuid dan berisi data pribadi peserta.
     */
    private const NOINDEX = [
        'calculator.result',
        'calculator.report',
    ];

    private function __construct(
        private readonly string $routeName,
        private readonly array $page,
    ) {}

    public static function current(): self
    {
        $routeName = (string) (Route::currentRouteName() ?? '');
        $key = self::PAGES[$routeName] ?? 'default';

        return new self($routeName, (array) __("seo.$key"));
    }

    public function title(): string
    {
        return $this->page['title'] ?? __('seo.default.title');
    }

    /** Judul untuk kartu WhatsApp/sosmed; dipotong lebih awal dari title. */
    public function shareTitle(): string
    {
        return $this->page['share_title'] ?? $this->title();
    }

    public function description(): string
    {
        return $this->page['description'] ?? __('seo.default.description');
    }

    public function keywords(): string
    {
        return $this->page['keywords'] ?? __('seo.default.keywords');
    }

    public function siteName(): string
    {
        return __('seo.site_name');
    }

    public function imageAlt(): string
    {
        return __('seo.image_alt');
    }

    /**
     * Gambar pratinjau, harus URL absolut — WhatsApp dan Facebook tidak
     * pernah menebak host dari path relatif.
     */
    public function image(): string
    {
        return asset('asset/images/og-cover.jpg');
    }

    /** URL kanonik tanpa query string, supaya ?utm_* tidak jadi halaman lain. */
    public function canonical(): string
    {
        return url()->current();
    }

    public function noindex(): bool
    {
        return in_array($this->routeName, self::NOINDEX, true)
            || str_starts_with($this->routeName, 'admin.');
    }

    /** Bahasa halaman dalam format Open Graph: id_ID / en_US. */
    public function ogLocale(): string
    {
        return app()->getLocale() === 'en' ? 'en_US' : 'id_ID';
    }

    public function isHome(): bool
    {
        return $this->routeName === 'home';
    }

    /**
     * Isi FAQ beranda. Dipakai dua kali — untuk markup yang terlihat dan
     * untuk structured data — agar keduanya tidak mungkin berbeda.
     *
     * @return array<int, array{q: string, a: string}>
     */
    public static function faq(): array
    {
        return (array) __('seo.faq');
    }

    /**
     * Structured data schema.org.
     *
     * Organization + WebSite menjelaskan penerbitnya, WebApplication
     * menjelaskan kalkulatornya, FAQPage membuat pertanyaan di beranda
     * berpeluang tampil langsung di hasil pencarian.
     *
     * @return array<string, mixed>
     */
    public function structuredData(): array
    {
        $graph = [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => $this->siteName(),
                'url' => url('/'),
                'logo' => asset('asset/images/logo.png'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => $this->siteName(),
                'description' => __('seo.default.description'),
                'publisher' => ['@id' => url('/').'#organization'],
                'inLanguage' => app()->getLocale(),
            ],
        ];

        if ($this->isHome()) {
            $graph[] = [
                '@type' => 'WebApplication',
                'name' => __('seo.default.title'),
                'url' => route('calculator'),
                'applicationCategory' => 'UtilitiesApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => app()->getLocale(),
                'description' => __('seo.home.description'),
                'publisher' => ['@id' => url('/').'#organization'],
                // Kalkulatornya gratis dan tanpa akun; offer bernilai 0
                // adalah cara schema.org menyatakan itu.
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'IDR',
                ],
            ];

            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $item) => [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
                ], self::faq()),
            ];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
