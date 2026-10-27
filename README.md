# Sistem Cetak Sertifikat Kejuaraan (SCS)

Aplikasi web monolitik untuk mengelola data peserta lomba beserta juara, lalu mencetak
**Sertifikat Juara**, **SK Juara**, dan **Sertifikat Coach/Official** langsung dari browser
dalam bentuk PDF.
| Lapisan | Teknologi |
|---|---|
| Framework | Laravel 13 (PHP 8.3) |
| Frontend | Blade + Tailwind CSS 4 (Vite) |
| PDF | DomPDF (`barryvdh/laravel-dompdf`) |
| Database | MySQL 8 (Laragon) |
| Deploy | Lokal + Cloudflare Tunnel (HTTPS publik) |

Dokumentasi lengkap ada di [`docs/`](docs/INDEX.md) — mulai dari **[docs/INDEX.md](docs/INDEX.md)**.

## Menjalankan di mesin ini

```powershell
# 1. Nyalakan MySQL (Laragon)
Start-Process "D:\Project\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe" `
  -ArgumentList "--defaults-file=D:\Project\laragon\bin\mysql\mysql-8.0.30-winx64\my.ini" -WindowStyle Hidden

# 2. Install dependency
composer install
npm install

# 3. Siapkan environment & database
copy .env.example .env
php artisan key:generate
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS sistem_cetak_sertifikat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS sistem_cetak_sertifikat_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan storage:link

# 4. Build aset & jalankan
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

## Deploy publik (P5 — Cloudflare Tunnel)

```powershell
# Named Tunnel (domain tetap: https://sbcc.my.id)
powershell -ExecutionPolicy Bypass -File tools/p5-start-named.ps1

# Quick Tunnel (fallback tanpa domain, URL acak)
powershell -ExecutionPolicy Bypass -File tools/p5-start.ps1

# Hentikan server + tunnel (Quick maupun Named)
powershell -ExecutionPolicy Bypass -File tools/p5-stop.ps1
```

- **Named Tunnel** memakai `tools/cloudflared-named.yml`, tunnel `sertifikat`, dan domain `sbcc.my.id`. URL tetap dan aman untuk hari-H.
- **Quick Tunnel** memakai `*.trycloudflare.com`; URL berubah setiap restart — hanya untuk UAT cepat.
- Env produksi (`APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`) hanya berlaku untuk proses yang dijalankan script; `.env` lokal tidak diubah.
- Timezone aplikasi memakai **`Asia/Jakarta` (WIB)** melalui `APP_TIMEZONE`; riwayat cetak dan timestamp baru mengikuti WIB.
- Saat mode P5 aktif, login lewat `http://localhost:8000` tidak mempertahankan sesi (cookie hanya untuk HTTPS). Gunakan URL HTTPS.
- Request HTTP otomatis dialihkan ke HTTPS oleh middleware `ForceHttps`; tetap aktifkan **Always Use HTTPS** di dashboard Cloudflare.
- Aktifkan **Cloudflare Access** untuk area admin sebelum URL dibagikan luas; halaman tamu `/cetak` tetap publik.
- Checklist UAT lengkap: [`docs/UAT_P5.md`](docs/UAT_P5.md).

## Akun demo (seed)

| Email | Password | Role |
|---|---|---|
| `admin@sistem.test` | `password` | admin |
| `operator@sistem.test` | `password` | operator |

> Ganti password sebelum dipakai sungguhan. Di mesin event ini password akun seed **sudah diganti**; jangan dikembalikan ke nilai default.

## Test

```powershell
npm run build      # wajib: layout memakai @vite
php artisan test
```

Status implementasi: **fase P0–P4 & P6 selesai; P5 sebagian** — Named Tunnel HTTPS `https://sbcc.my.id` aktif, Cloudflare Access & UAT cetak fisik menunggu (lihat `docs/UAT_P5.md`).

## Fitur

- **Cetak sertifikat & SK** untuk peserta (satuan maupun bulk) — butuh login panitia.
- **Cetak tanpa login** (`/cetak`) — peserta & pelatih bisa mengunduh sertifikatnya sendiri; dibatasi rate limit.
- **Blangko dua mode** (`/blangko`) - (a) **desainer kanvas**: unggah gambar blangko (sertifikat/SK), letakkan teks dengan drag & drop (satuan milimeter); atau (b) **template Blade**: pakai file `.blade.php` dari `resources/views/blangko/`. Isi dapat diambil dari database, teks bebas, atau varian. Ukuran kertas mengikuti preset **A4 / F4 / Letter / Custom** + orientasi (dimensi mm dihitung otomatis).
- **Varian otomatis** — kelompok usia *Pra Usia Dini* & *Usia Dini 1* memakai sertifikat **pemasalan**, lainnya **prestasi**.
