// Livewire membundel dan menjalankan Alpine sendiri (window.Alpine).
// JANGAN import atau start alpinejs di sini — akan memicu peringatan
// "Detected multiple instances of Alpine" dan bug reaktivitas yang senyap.
//
// File ini sengaja dipertahankan sebagai entrypoint Vite (dirujuk @vite()
// di resources/views/layouts/app.blade.php). Daftarkan directive atau
// plugin Alpine kustom dari sini bila nanti dibutuhkan.

// SweetAlert2 dipasang di window supaya bisa dipanggil langsung dari
// ekspresi Alpine (x-on:click="Swal.fire(...)") tanpa import per-halaman.
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

// Dialog konfirmasi sebelum keluar dari wizard kalkulator (dipanggil dari
// x-on:click di resources/views/pages/calculator.blade.php). Teksnya dibaca
// dari data-attribute di link supaya tetap ikut terjemahan id/en, dan
// navigasinya lewat Livewire.navigate supaya transisi SPA-nya tetap terjaga.
window.confirmCalculatorExit = (link) => {
    Swal.fire({
        title: link.dataset.confirmTitle,
        text: link.dataset.confirmText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: link.dataset.confirmYes,
        cancelButtonText: link.dataset.confirmNo,
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            window.Livewire.navigate(link.href);
        }
    });
};

// Wizard kalkulator: tombol "Lanjut"/"Kembali" ada di bagian bawah halaman,
// sedangkan judul step berikutnya dan kotak pesan error ada di paling atas.
// Tanpa penyesuaian scroll, di mobile user mendarat di tengah-tengah dan
// harus menggulir manual ke atas — atau, saat validasi gagal, mengira
// tombolnya tidak bereaksi karena pesannya tidak terlihat.
// Event-nya dikirim dari resources/views/pages/calculator.blade.php.
const calculatorScrollBehavior = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

document.addEventListener('livewire:init', () => {
    // Pindah step: kembali ke paling atas (judul + progress bar).
    // requestAnimationFrame dipakai supaya gulirannya berjalan setelah
    // Livewire selesai mengganti DOM step.
    window.Livewire.on('calculator-step-changed', () => {
        requestAnimationFrame(() => {
            window.scrollTo({ top: 0, behavior: calculatorScrollBehavior() });
        });
    });

    // Validasi gagal: step tidak berpindah, jadi yang dituju adalah pertanyaan
    // pertama yang masih kosong — dipilih berdasarkan urutan tampil di halaman,
    // bukan urutan aturan validasi. Kalau penandanya tidak ketemu, jatuh ke
    // kotak ringkasan error di atas form.
    window.Livewire.on('calculator-validation-failed', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        const failed = new Set(data?.fields ?? []);

        requestAnimationFrame(() => {
            const target =
                [...document.querySelectorAll('[data-calc-field]')]
                    .find((el) => failed.has(el.dataset.calcField))
                ?? document.querySelector('[data-calc-error-summary]');

            if (! target) {
                return;
            }

            target.scrollIntoView({ behavior: calculatorScrollBehavior(), block: 'center' });
        });
    });
});
