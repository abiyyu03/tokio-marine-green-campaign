// Kartu "Bagikan Hasilmu" di halaman hasil: x-data="resultShare({...})".
//
// Facebook punya URL share resmi, jadi tombolnya cukup tautan biasa di Blade.
// Instagram dan TikTok tidak punya — keduanya tidak menerima tautan dari web,
// hanya gambar/video. Tombol keduanya karena itu menggambar kartu hasil
// seukuran Story (1080x1920) di <canvas>, lalu:
//   1. membuka share sheet bawaan perangkat (Web Share API dengan berkas),
//      tempat peserta memilih Instagram/TikTok dan langsung membuat postingan;
//   2. kalau tidak didukung (desktop, sebagian in-app browser) atau gagal,
//      menampilkan pratinjau gambar dengan tombol unduh.
//
// Gambarnya disiapkan begitu kartu tampil, bukan saat tombol diklik: Safari
// menolak navigator.share() yang dipanggil setelah jeda async panjang dari
// klik (NotAllowedError), jadi berkasnya harus sudah siap saat itu.

const WIDTH = 1080;
const HEIGHT = 1920;
const FONT = '"Instrument Sans", ui-sans-serif, system-ui, sans-serif';
const FALLBACK_COLOR = '#0d9488';

window.resultShare = (config) => {
    // Berkas dan blob URL sengaja di luar state Alpine: objek di state
    // dibungkus Proxy, dan navigator.share() menolak File yang ter-proxy.
    let file = null;
    let pending = null;
    let previewUrl = null;

    const prepare = () => {
        pending ??= drawCard(config.card, config.fileName)
            .then((result) => (file = result))
            .catch((error) => {
                pending = null;
                throw error;
            });

        return pending;
    };

    return {
        busy: null,
        failed: false,
        preview: null,

        init() {
            const warmUp = () => prepare().catch(() => {});

            'requestIdleCallback' in window ? requestIdleCallback(warmUp, { timeout: 2000 }) : setTimeout(warmUp, 300);
        },

        async shareImage(platform) {
            if (this.busy) return;

            this.busy = platform;
            this.failed = false;

            try {
                const card = file ?? (await prepare());

                if (await shareNatively(card, config.title)) return;

                previewUrl ??= URL.createObjectURL(card);
                this.preview = { src: previewUrl, platform };
            } catch {
                this.failed = true;
            } finally {
                this.busy = null;
            }
        },

        closePreview() {
            this.preview = null;
        },
    };
};

/** true bila share sheet terbuka (termasuk bila ditutup sendiri oleh peserta). */
async function shareNatively(card, title) {
    if (!navigator.canShare?.({ files: [card] })) return false;

    try {
        // Hanya berkas, tanpa text/url: di iOS, berkas yang disertai tautan
        // bisa membuat Instagram tidak muncul di daftar aplikasi.
        await navigator.share({ files: [card], title });

        return true;
    } catch (error) {
        return error?.name === 'AbortError';
    }
}

async function drawCard(card, fileName) {
    const [logo] = await Promise.all([loadImage(card.logo), loadFonts()]);

    // Warna kartu mengikuti tier hasil (kolom color, sama dengan badge di
    // halaman): hijau Green Starter, cokelat Earth Supporter, merah Climate Mover.
    const theme = /^#[0-9a-f]{6}$/i.test(card.badgeColor ?? '') ? card.badgeColor : FALLBACK_COLOR;

    const canvas = document.createElement('canvas');
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const ctx = canvas.getContext('2d');

    const background = ctx.createLinearGradient(0, 0, 0, HEIGHT);
    background.addColorStop(0, mix(theme, '#ffffff', 0.3));
    background.addColorStop(1, theme);
    ctx.fillStyle = background;
    ctx.fillRect(0, 0, WIDTH, HEIGHT);

    ctx.fillStyle = 'rgba(255, 255, 255, 0.08)';
    fillCircle(ctx, 980, 260, 300);
    fillCircle(ctx, 60, 1660, 340);

    // Isi penting dijaga di antara y=250 dan y=1670: di luar itu tertutup
    // nama akun dan kolom balasan Instagram/TikTok Story.
    roundRect(ctx, 250, 262, 580, 116, 58);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    if (logo) {
        const height = 66;
        const width = Math.min((logo.width * height) / logo.height, 500);
        ctx.drawImage(logo, (WIDTH - width) / 2, 320 - height / 2, width, height);
    }

    drawText(ctx, card.lockup.toUpperCase(), 440, { size: 30, color: 'rgba(255, 255, 255, 0.92)', spacing: 5 });

    // Kartu putih berisi angka utama.
    ctx.save();
    ctx.shadowColor = 'rgba(15, 23, 42, 0.18)';
    ctx.shadowBlur = 60;
    ctx.shadowOffsetY = 20;
    roundRect(ctx, 80, 510, 920, 880, 56);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    ctx.restore();

    drawText(ctx, card.eyebrow.toUpperCase(), 610, { size: 34, color: theme, spacing: 4 });
    drawText(ctx, card.value, 780, { size: 230, color: '#0f172a', maxWidth: 820 });
    drawText(ctx, card.unit, 925, { size: 48, weight: 600, color: '#475569' });

    if (card.badgeLabel) {
        drawBadge(ctx, card.badgeLabel, theme, card.badgeIcon, 1030);
    }

    drawText(ctx, card.score, 1150, { size: 40, weight: 600, color: '#334155' });

    ctx.fillStyle = '#e2e8f0';
    ctx.fillRect(200, 1218, WIDTH - 400, 3);

    drawText(ctx, card.footnote, 1295, { size: 32, weight: 500, color: '#64748b' });

    // Ajakan + alamat situs.
    drawText(ctx, card.cta, 1480, { size: 54, color: '#ffffff' });

    ctx.font = `700 46px ${FONT}`;
    const hostWidth = Math.min(ctx.measureText(card.host).width + 120, 900);
    roundRect(ctx, (WIDTH - hostWidth) / 2, 1545, hostWidth, 100, 50);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
    drawText(ctx, card.host, 1595, { size: 46, color: theme, maxWidth: hostWidth - 80 });

    const blob = await new Promise((resolve, reject) => {
        canvas.toBlob((result) => (result ? resolve(result) : reject(new Error('Kartu gagal dibuat'))), 'image/png');
    });

    return new File([blob], fileName, { type: 'image/png' });
}

/** Pil badge tier seperti di halaman hasil: ikon + label di atas warna tier tipis. */
function drawBadge(ctx, label, color, iconPath, centerY) {
    const height = 100;
    const iconSize = 44;
    const gap = 16;

    ctx.font = `700 44px ${FONT}`;
    const labelWidth = Math.min(ctx.measureText(label).width, 640);
    const icon = iconPath && typeof Path2D === 'function' ? new Path2D(iconPath) : null;
    const contentWidth = labelWidth + (icon ? iconSize + gap : 0);
    const width = contentWidth + 100;
    const x = (WIDTH - width) / 2;

    roundRect(ctx, x, centerY - height / 2, width, height, height / 2);
    ctx.fillStyle = withAlpha(color, 0.12);
    ctx.fill();

    let cursor = x + 50;
    ctx.fillStyle = color;

    if (icon) {
        // Path ikon memakai viewBox 20x20 (lihat components/emission-icon).
        ctx.save();
        ctx.translate(cursor, centerY - iconSize / 2);
        ctx.scale(iconSize / 20, iconSize / 20);
        ctx.fill(icon, 'evenodd');
        ctx.restore();
        cursor += iconSize + gap;
    }

    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';
    ctx.fillText(label, cursor, centerY, 640);
}

/** Teks rata tengah; ukurannya diperkecil sampai muat di maxWidth. */
function drawText(ctx, text, y, { size, weight = 700, color, maxWidth = 900, spacing = 0 }) {
    const hasSpacing = 'letterSpacing' in ctx;
    if (hasSpacing) ctx.letterSpacing = `${spacing}px`;

    let px = size;
    ctx.font = `${weight} ${px}px ${FONT}`;
    while (px > 16 && ctx.measureText(text).width > maxWidth) {
        px -= 2;
        ctx.font = `${weight} ${px}px ${FONT}`;
    }

    ctx.fillStyle = color;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, WIDTH / 2, y);

    if (hasSpacing) ctx.letterSpacing = '0px';
}

// Bukan ctx.roundRect(): belum ada di Safari < 16.
function roundRect(ctx, x, y, width, height, radius) {
    ctx.beginPath();
    ctx.moveTo(x + radius, y);
    ctx.arcTo(x + width, y, x + width, y + height, radius);
    ctx.arcTo(x + width, y + height, x, y + height, radius);
    ctx.arcTo(x, y + height, x, y, radius);
    ctx.arcTo(x, y, x + width, y, radius);
    ctx.closePath();
}

function fillCircle(ctx, x, y, radius) {
    ctx.beginPath();
    ctx.arc(x, y, radius, 0, Math.PI * 2);
    ctx.fill();
}

function rgb(hex) {
    const value = parseInt(hex.slice(1), 16);

    return [value >> 16, (value >> 8) & 255, value & 255];
}

function withAlpha(hex, alpha) {
    const [r, g, b] = rgb(hex);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/** Campuran dua warna hex; amount 0 = warna pertama, 1 = warna kedua. */
function mix(hex, other, amount) {
    const from = rgb(hex);
    const to = rgb(other);
    const channel = (i) => Math.round(from[i] + (to[i] - from[i]) * amount);

    return `rgb(${channel(0)}, ${channel(1)}, ${channel(2)})`;
}

function loadImage(src) {
    return new Promise((resolve) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => resolve(null);
        image.src = src;
    });
}

// Instrument Sans dimuat dari fonts.bunny.net; kalau gagal, canvas memakai
// font sistem — kartunya tetap jadi.
async function loadFonts() {
    if (!document.fonts?.load) return;

    try {
        await Promise.all(['500', '600', '700'].map((weight) => document.fonts.load(`${weight} 40px "Instrument Sans"`)));
    } catch {
        // abaikan
    }
}
