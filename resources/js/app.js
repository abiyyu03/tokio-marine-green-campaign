// Livewire membundel dan menjalankan Alpine sendiri (window.Alpine).
// JANGAN import atau start alpinejs di sini — akan memicu peringatan
// "Detected multiple instances of Alpine" dan bug reaktivitas yang senyap.
//
// File ini sengaja dipertahankan sebagai entrypoint Vite (dirujuk @vite()
// di resources/views/layouts/app.blade.php). Daftarkan directive atau
// plugin Alpine kustom dari sini bila nanti dibutuhkan.
