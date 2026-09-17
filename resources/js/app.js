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
