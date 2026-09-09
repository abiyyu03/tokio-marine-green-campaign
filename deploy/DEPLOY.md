# Deploy ke shared hosting (tanpa SSH, tanpa Node.js, tanpa cron)

Prosedur untuk paket shared hosting yang mencoret **Akses SSH**, **Node.JS**, dan
**Redis**, tidak punya menu **Cron Jobs**, dan document root domain utamanya
terkunci di `public_html`.

Konsekuensinya: **tidak ada satu pun perintah `artisan`, `composer`, atau `npm`
yang bisa dijalankan di server.** Semuanya disiapkan di laptop, lalu dikirim
sebagai file.

Prasyarat di hosting: **PHP 8.3+** (Laravel 13 mensyaratkannya) dan MariaDB.

Ekstensi PHP yang harus aktif: `mbstring`, `pdo_mysql`, `zip`, `dom`,
`libxml`, `xmlreader`, `fileinfo`. Lima yang terakhir dipakai penulis
Excel di area admin (openspout) — tanpa `zip`, tombol **Unduh Excel**
akan gagal sementara CSV tetap jalan. Cek lewat file `phpinfo()` kalau
panel hosting tidak menampilkannya.

---

## Tata letak di server

```
/home/USER/
├── laravel/            <- seluruh aplikasi kecuali isi public/
│   ├── app/ bootstrap/ config/ database/ lang/ resources/ routes/ storage/ vendor/
│   ├── .env            <- di luar public_html, tidak bisa diakses lewat URL
│   ├── .htaccess       <- tolak semua akses (dari .htaccess di root repo)
│   └── artisan
└── public_html/        <- document root
    ├── index.php       <- dari deploy/index.php
    ├── .htaccess       <- pengamanan (dari public/.htaccess)
    ├── favicon.ico
    ├── robots.txt
    └── build/          <- hasil `npm run build`
```

---

## 1. Menyiapkan paket di laptop

```bash
# a. Dependensi produksi saja. Memangkas vendor/ dari ~93 MB / 8.874 file
#    menjadi ~57 MB, sekaligus menghemat inode di hosting.
composer install --no-dev --optimize-autoloader

# b. Build aset. Inilah alasan tidak adanya Node.js di server tidak masalah:
#    Vite hanya dipakai di sini, keluarannya file statis.
npm run build

# c. Bersihkan cache lokal supaya tidak ikut terkirim.
php artisan optimize:clear

# d. Susun folder staging
rm -rf deploy/build
mkdir -p deploy/build/laravel deploy/build/public_html

rsync -a \
  --exclude='public/' --exclude='node_modules/' --exclude='.git/' \
  --exclude='deploy/' --exclude='.env' --exclude='.env.bak*' \
  ./ deploy/build/laravel/

cp -r public/. deploy/build/public_html/
cp deploy/index.php deploy/build/public_html/index.php

# e. Dua zip terpisah supaya upload File Manager tidak timeout
cd deploy/build
zip -rq ../laravel.zip laravel
zip -rq ../public_html.zip public_html
cd ../..
```

Kedua zip berisi folder induk (`laravel/` dan `public_html/`), jadi keduanya
di-extract di `/home/USER/` dan langsung mendarat di tempat yang benar.

```bash
# f. Kembalikan dependensi development untuk lanjut ngoding
composer install
```

---

## 2. Menyiapkan database

```bash
php artisan migrate:fresh --seed
mysqldump -u root --no-tablespaces db_freen_camp_tm > deploy/schema-seed.sql
```

Periksa dump-nya sebelum diimpor:

```bash
grep -c 'utf8mb4_0900_ai_ci' deploy/schema-seed.sql
```

Kalau hasilnya bukan `0`, MySQL lokal Anda menulis collation yang **tidak ada di
MariaDB** dan impor akan gagal. Perbaiki dulu:

```bash
sed -i 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' deploy/schema-seed.sql
```

---

## 3. Di cPanel

1. **File Manager** → kosongkan `public_html` dari bawaan hosting
   (`index.html`, default builder, dsb).
2. Upload `laravel.zip` dan `public_html.zip` ke `/home/USER/`, lalu **Extract**
   keduanya di sana.
3. Buat `/home/USER/laravel/.env` — salin dari
   [`.env.production.example`](.env.production.example), isi kredensial DB, dan
   isi `APP_KEY` dengan hasil `php artisan key:generate --show` di lokal
   (**key baru**, jangan pakai ulang key development).
4. **Email Accounts** → buat mailbox pengirim, mis. `noreply@domain-anda.com`,
   lalu isi blok `MAIL_*` di `.env` dengan kredensialnya (lihat
   [Email hasil kalkulator](#email-hasil-kalkulator)).
5. **MySQL Databases** → buat database + user, beri `ALL PRIVILEGES`.
6. **phpMyAdmin** → pilih database → **Import** → `schema-seed.sql`.
7. **Select PHP Version** → set **8.3+**, lalu aktifkan **OPcache**. Ini
   penurun beban CPU terbesar yang bisa didapat tanpa SSH, dan penting karena
   aplikasi berjalan tanpa config/route cache.
8. **Permission**: `storage/` dan `bootstrap/cache/` beserta seluruh isinya
   → **755**. PHP berjalan sebagai user akun di CloudLinux, jadi 777 tidak
   diperlukan dan justru memperluas permukaan serangan.

**Verifikasi pertama:** set `APP_DEBUG=true` sementara di `.env`, buka situs,
pastikan tidak ada error, lalu kembalikan ke `false`.

---

## 4. Uji setelah live

1. Buka `/`, lalu `/kalkulator`.
2. Klik satu opsi — ini membuktikan endpoint Livewire hidup dan penulisan ke
   `submission_values` berhasil.
3. Selesaikan wizard sampai halaman hasil.
4. Ganti bahasa ke **EN** lewat dropdown header — membuktikan session driver
   `database` bekerja.
5. phpMyAdmin → cek tabel `leads` dan `submissions` bertambah barisnya.
6. Cek kotak masuk email yang dipakai di langkah 3: email hasil harus tiba,
   dan kedua tombolnya (**Lihat Hasil Lengkap**, **Unduh Laporan**) harus
   membuka halaman yang benar. Cek juga folder spam.
7. Pastikan `storage/logs/laravel-*.log` bersih.

---

## Email hasil kalkulator

Begitu peserta menekan tombol terakhir wizard, aplikasi mengirim satu email
berisi ringkasan hasil, tautan ke halaman hasilnya, dan tautan ke laporan siap
cetak. Tautannya berkunci `uuid` dan tidak butuh login — itulah satu-satunya
jalan peserta kembali ke hasilnya setelah tab ditutup, sebab aplikasi ini
memang tidak punya akun pengunjung.

**Setelan.** Semuanya di blok `MAIL_*` pada `.env`
([template](.env.production.example)). Pakai mailbox cPanel milik domain yang
sama: SPF/DKIM-nya sudah cocok, jadi kemungkinan masuk spam jauh lebih kecil
daripada memakai Gmail pribadi sebagai SMTP.

| Kunci | Isi |
| --- | --- |
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | biasanya `mail.domain-anda.com` |
| `MAIL_PORT` + `MAIL_SCHEME` | `465` + `smtps` (SSL) **atau** `587` + `smtp` (STARTTLS) |
| `MAIL_USERNAME` | alamat mailbox lengkap |
| `MAIL_FROM_ADDRESS` | **harus sama** dengan `MAIL_USERNAME` |
| `APP_URL` | wajib benar — dipakai membentuk tautan di email |

**Konsekuensi queue `sync`.** Tanpa cron, email dikirim di dalam request yang
menekan tombol terakhir: langkah itu terasa 1-3 detik lebih lambat. Sebagai
gantinya tidak ada job yang menumpuk tanpa pernah dieksekusi.

**Kalau pengiriman gagal.** Hasil peserta tetap tersimpan dan halaman hasilnya
tetap terbuka — kegagalan SMTP tidak pernah berubah menjadi layar error. Yang
berubah hanya kotak notifikasi di halaman hasil: ia berganti menjadi
"Email Belum Terkirim" dan meminta peserta menyimpan tautan halaman itu.
Penyebabnya dicatat di `storage/logs/laravel-*.log` dengan pesan
`Gagal mengirim email hasil kalkulator`.

**Mengirim ulang.** Admin > Peserta > buka pesertanya > baris **Email hasil** >
**Kirim ulang**. Ini satu-satunya jalur pemulihan yang tersedia: tanpa SSH,
`php artisan` tidak bisa dijalankan di server.

**Kalau situsnya sudah live sebelum fitur ini.** Deploy baru dari
`schema-seed.sql` sudah membawa kolom penandanya. Untuk database yang sudah
berisi data peserta, jangan impor ulang — cukup jalankan satu perintah ini di
**phpMyAdmin > SQL**:

```sql
ALTER TABLE `submissions`
  ADD `result_email_sent_at` TIMESTAMP NULL DEFAULT NULL AFTER `completed_at`;
```

Baris lama otomatis terbaca "belum pernah dikirimi email", yang memang benar.

---

## Verifikasi pengamanan .htaccess

Dua berkas ikut terkirim otomatis oleh langkah 1d:

| Berkas                  | Mendarat di                | Tugasnya |
|-------------------------|----------------------------|----------|
| `public/.htaccess`      | `public_html/.htaccess`    | header keamanan, kunci eksekusi PHP, blokir berkas sensitif |
| `.htaccess` (root repo) | `laravel/.htaccess`        | tolak semua akses, kalau-kalau ada subdomain salah arah ke folder aplikasi |

Aturannya diuji di Apache 2.4 lokal sebelum dikirim, tapi **Rumahweb memakai
LiteSpeed**, bukan Apache. Setelah upload, buktikan sendiri dari terminal —
ganti `domain-anda.com`:

```bash
D=https://domain-anda.com

# 1. Harus 403 semua. Kalau ada yang 200, hentikan dan periksa .htaccess.
for f in /.env /.git/config /composer.json /artisan /storage.sql; do
  echo -n "$f -> "; curl -s -o /dev/null -w "%{http_code}\n" "$D$f"
done

# 2. Harus 200 semua.
for f in / /kalkulator /build/manifest.json; do
  echo -n "$f -> "; curl -s -o /dev/null -w "%{http_code}\n" "$D$f"
done

# 3. Header keamanan harus muncul.
curl -sI "$D/" | grep -iE "content-security|x-frame|x-content-type|referrer|strict-transport"

# 4. Area admin tidak boleh terindeks.
curl -sI "$D/admin/masuk" | grep -i x-robots-tag

# 5. http harus dilempar ke https.
curl -sI "http://domain-anda.com/" | head -1     # harapkan 301
```

Dua hal yang **tidak bisa** diurus dari .htaccess, jadi periksa terpisah:

- **Header `Server`.** Versi server hanya bisa disembunyikan lewat
  `ServerTokens` di konfigurasi server. Tidak fatal, tapi kalau `curl -sI $D/`
  memunculkan versi lengkap, itu memang di luar kendali kita.
- **Metode TRACE.** `curl -X TRACE $D/` tidak boleh memantulkan kembali header
  Anda. LiteSpeed normalnya tidak mengenal TRACE sama sekali; kalau ternyata
  memantul, itu perlu diminta ke CS Rumahweb — `.htaccess` tidak bisa
  menutupnya.

Setelan PHP (`display_errors`, `expose_php`) juga tidak bisa lewat `.htaccess`
di LSAPI. Pakai **cPanel > MultiPHP INI Editor**, dan pastikan
`display_errors = Off`.

---

## Hal yang gampang bikin celaka

- **Jangan upload `bootstrap/cache/config.php`** hasil `php artisan config:cache`
  dari laptop. File itu menyimpan path absolut mesin lokal
  (`/home/abiyyucakra/...`) dan akan merusak aplikasi di server. Tanpa cron,
  aplikasi ini memang berjalan tanpa config/route cache — selisihnya belasan
  milidetik dan sudah tertutup OPcache. Langkah 1c di atas mencegahnya ikut.
- **Request pertama setelah upload akan lambat.** Livewire 4 mengkompilasi
  komponen SFC saat runtime ke `storage/framework/views/livewire/`. Kalau
  `storage/` tidak writable hasilnya **HTTP 500**, dan dengan `APP_DEBUG=false`
  halamannya kosong tanpa petunjuk. Cek `storage/logs/laravel-*.log`.
- **`QUEUE_CONNECTION` harus tetap `sync`.** Tanpa SSH/cron tidak ada
  `queue:work`; driver lain membuat job menumpuk tanpa pernah dieksekusi.
- **Jangan pernah ikutkan `.env` atau `.env.bak*` ke dalam zip.** Perintah
  `rsync` di langkah 1d sudah mengecualikannya.

---

## Operasi rutin tanpa artisan

**Membersihkan cache:** hapus isi `bootstrap/cache/*.php` dan
`storage/framework/views/*` lewat File Manager. Blade dan Livewire
mengkompilasi ulang sendiri di request berikutnya.

**Memperbarui konten kalkulator** (pertanyaan, poin, faktor emisi, daftar Rumah
Pilah): jalankan seeder di lokal, ekspor **hanya tabel master**, lalu impor:

```bash
mysqldump -u root --no-tablespaces db_freen_camp_tm \
  emission_categories emission_category_translations \
  emission_fields emission_field_translations \
  emission_field_options emission_field_option_translations \
  emission_factors emission_benchmarks emission_benchmark_translations \
  emission_equivalences emission_equivalence_translations \
  result_tiers result_tier_translations \
  recommendations recommendation_translations \
  drop_off_points community_impacts community_impact_translations \
  > deploy/master-data.sql
```

Tabel `leads`, `submissions`, `submission_values`, `submission_results`, dan
`submission_category_results` **jangan disentuh** — di situlah data pengunjung.
Sebelum impor apa pun, ekspor dulu database yang sedang berjalan lewat
phpMyAdmin; backup hosting hanya mingguan.

**Memperbarui kode:** ulangi langkah 1, lalu upload ulang. Untuk perubahan kecil
yang hanya menyentuh Blade/PHP, cukup upload file yang berubah dan hapus
`storage/framework/views/*`.
