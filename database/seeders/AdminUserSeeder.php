<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun admin pertama.
 *
 * Kredensialnya dibaca dari .env dan TIDAK ADA nilai default: seeder
 * ber-password bawaan adalah salah satu cara paling umum sebuah panel admin
 * bocor — sekali `db:seed` di server, akun tebakan itu hidup di produksi
 * tanpa ada yang sadar. Tanpa ADMIN_EMAIL dan ADMIN_PASSWORD, seeder ini
 * memilih tidak membuat apa pun.
 *
 * Idempoten: dikunci pada kolom email, jadi menjalankannya ulang memperbarui
 * akun yang sama, bukan menggandakannya.
 *
 * Catatan produksi: server shared hosting proyek ini tidak bisa menjalankan
 * artisan sama sekali (lihat deploy/DEPLOY.md), jadi hash password ikut
 * dicetak agar bisa dipasang lewat phpMyAdmin bila perlu.
 */
class AdminUserSeeder extends Seeder
{
    /** Panjang minimum yang diterima; di bawah ini seeder menolak jalan. */
    private const MIN_PASSWORD_LENGTH = 12;

    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            $this->command?->warn(
                'AdminUserSeeder dilewati: isi ADMIN_EMAIL dan ADMIN_PASSWORD di .env lebih dulu.'
            );

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->error('AdminUserSeeder dibatalkan: ADMIN_EMAIL bukan alamat email yang sah.');

            return;
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $this->command?->error(
                'AdminUserSeeder dibatalkan: ADMIN_PASSWORD minimal '.self::MIN_PASSWORD_LENGTH.' karakter.'
            );

            return;
        }

        $user = User::query()->where('email', $email)->first() ?? new User;
        $isNew = ! $user->exists;

        $user->name = trim((string) env('ADMIN_NAME', 'Admin')) ?: 'Admin';
        $user->email = $email;
        $user->password = $password;   // cast 'hashed' di User::casts() yang meng-hash
        $user->email_verified_at ??= now();
        $user->save();

        $this->command?->info(($isNew ? 'Admin dibuat: ' : 'Admin diperbarui: ').$email);
        $this->command?->line('  hash: '.$user->password);
    }
}
