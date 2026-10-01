<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

/**
 * Draf awal Syarat & Ketentuan yang disetujui peserta di step terakhir
 * kalkulator. Teks ini SEMENTARA — revisi berikutnya dilakukan langsung di
 * tabel legal_document_translations (kolom body, format Markdown), lalu
 * naikkan legal_documents.version supaya persetujuan baru tercatat dengan
 * versi yang benar.
 *
 * Karena itu seeder ini hanya MENGISI BILA BELUM ADA: menjalankannya ulang
 * tidak akan menimpa teks yang sudah diedit di database.
 */
class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::documents() as $data) {
            $document = LegalDocument::firstOrCreate(
                ['code' => $data['code']],
                ['version' => $data['version'], 'is_active' => true],
            );

            foreach ($data['translations'] as $locale => $text) {
                $document->translations()->firstOrCreate(['locale' => $locale], $text);
            }
        }
    }

    /**
     * Juga dipakai untuk menyusun INSERT di deploy/sql/ — ubah di sini, jangan
     * di file SQL-nya langsung.
     *
     * @return array<int, array{code: string, version: string, translations: array<string, array{title: string, body: string}>}>
     */
    public static function documents(): array
    {
        return [
            [
                'code' => LegalDocument::TERMS,
                'version' => '2026-10-draft',
                'translations' => [
                    'id' => [
                        'title' => 'Syarat & Ketentuan Kalkulator Karbon',
                        'body' => <<<'MD'
> **DRAF SEMENTARA** — teks ini masih menunggu peninjauan. Bagian dalam [kurung siku] perlu dilengkapi.

## 1. Tentang Program
Kalkulator Karbon adalah program kampanye lingkungan yang diselenggarakan oleh Tokio Marine Life bersama Dompet Dhuafa ("Penyelenggara"), berlangsung pada [tanggal mulai] sampai [tanggal selesai].

## 2. Hasil Perhitungan
- Hasil jejak karbon merupakan **estimasi** berdasarkan jawaban yang kamu berikan dan faktor emisi rata-rata.
- Hasil ini bersifat edukatif — bukan audit, sertifikasi, atau nasihat profesional — dan tidak dapat dijadikan dasar klaim apa pun.

## 3. Data yang Dikumpulkan
- Nama, alamat email, dan nomor WhatsApp.
- Tanggal lahir dan jenis kelamin, bila kamu mengisinya.
- Jawaban kuesioner kalkulator.
- Informasi teknis: alamat IP dan jenis perangkat/peramban.

## 4. Penggunaan Data
Data kamu digunakan untuk:
- menghitung dan mengirimkan hasil jejak karbon ke email kamu;
- menghubungi kamu terkait program ini melalui email atau WhatsApp;
- menyusun statistik program secara agregat yang tidak mengidentifikasi individu.

Penyelenggara tidak menjual data pribadimu dan tidak membagikannya kepada pihak ketiga di luar Penyelenggara, kecuali diwajibkan oleh peraturan perundang-undangan.

## 5. Penyimpanan & Keamanan
Data disimpan selama [periode penyimpanan] dan dilindungi sesuai Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.

## 6. Hak Peserta
Kamu dapat meminta akses, perbaikan, atau penghapusan data pribadimu dengan menghubungi marketing.com@tokiomarine-life.co.id.

## 7. Membagikan Hasil
Tautan halaman hasil dapat dibuka oleh siapa pun yang menerimanya. Membagikan tautan atau gambar hasil ke orang lain maupun media sosial menjadi tanggung jawabmu.

## 8. Perubahan Ketentuan
Penyelenggara dapat memperbarui Syarat & Ketentuan ini sewaktu-waktu. Versi yang kamu setujui tercatat bersama data partisipasimu.

## 9. Kontak
Pertanyaan seputar program ini dapat disampaikan melalui email marketing.com@tokiomarine-life.co.id atau WhatsApp +62-878-6150-0086.

Dengan mencentang kotak persetujuan, kamu menyatakan telah membaca, memahami, dan menyetujui Syarat & Ketentuan ini.
MD,
                    ],
                    'en' => [
                        'title' => 'Carbon Calculator Terms & Conditions',
                        'body' => <<<'MD'
> **TEMPORARY DRAFT** — this text is pending review. Items in [square brackets] still need to be completed.

## 1. About the Programme
The Carbon Calculator is an environmental campaign run by Tokio Marine Life together with Dompet Dhuafa (the "Organisers"), from [start date] to [end date].

## 2. Calculation Results
- Your carbon footprint result is an **estimate** based on your answers and average emission factors.
- The result is for educational purposes only — not an audit, certification, or professional advice — and cannot be used as the basis for any claim.

## 3. Data We Collect
- Your name, email address, and WhatsApp number.
- Your date of birth and gender, if you provide them.
- Your answers to the calculator questions.
- Technical information: IP address and device/browser type.

## 4. How We Use Your Data
Your data is used to:
- calculate your carbon footprint and send the result to your email;
- contact you about this programme by email or WhatsApp;
- compile aggregate programme statistics that do not identify individuals.

The Organisers do not sell your personal data and do not share it with third parties outside the Organisers, unless required by law.

## 5. Storage & Security
Your data is kept for [retention period] and protected in accordance with Law No. 27 of 2022 on Personal Data Protection.

## 6. Your Rights
You may request access to, correction of, or deletion of your personal data by contacting marketing.com@tokiomarine-life.co.id.

## 7. Sharing Your Result
Anyone who receives the link to your result page can open it. Sharing the link or result image with others or on social media is your responsibility.

## 8. Changes to These Terms
The Organisers may update these Terms & Conditions at any time. The version you agreed to is recorded with your participation data.

## 9. Contact
Questions about this programme can be sent to marketing.com@tokiomarine-life.co.id or via WhatsApp at +62-878-6150-0086.

By ticking the consent box, you confirm that you have read, understood, and agree to these Terms & Conditions.
MD,
                    ],
                ],
            ],
        ];
    }
}
