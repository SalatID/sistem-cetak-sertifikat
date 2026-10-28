# UAT P5 — Deploy HTTPS (Cloudflare Tunnel) & Uji Terima

| Field | Nilai |
|---|---|
| Proyek | Sistem Cetak Sertifikat (SCS) |
| Fase | **P5 — Deploy, Hardening, UAT** |
| Tanggal checklist | 23 Oktober 2026 (diperbarui 28 Oktober 2026) |
| Lingkungan | Windows + Laragon (MySQL 8.0.30) + PHP 8.3.29 + `php artisan serve` port 8000 |
| Tunnel | **Cloudflare Named Tunnel** — `https://sbcc.my.id` (tunnel `sertifikat`) |
| Status | 🟡 **Sebagian** — HTTPS & alur aplikasi lolos; Cloudflare Access, uji dari perangkat luar, dan UAT cetak fisik menunggu |

> Named Tunnel memakai domain tetap `https://sbcc.my.id`, aman untuk hari-H. Detail teknis ada di `docs/TRD.md` §10.2.

---

## 1. Ringkasan Hasil Otomatis

| Pemeriksaan | Hasil | Bukti |
|---|---|---|
| Test suite | ✅ **144/144 lulus, 533 assertion** | `php artisan test` |
| Filter kelompok usia `/peserta` | ✅ Opsi diambil dari nilai unik non-kosong; hasil filter & pagination tetap membawa query | `PesertaTest` (11 test, 43 assertion) |
| Edit peserta: dropdown kelompok usia | ✅ Nilai lama huruf besar (`PRA REMAJA`) tetap terpilih sebagai `Pra Remaja` | `PesertaTest` |
| Pint (gaya kode) | ✅ Lulus | `vendor/bin/pint --test` |
| Build frontend | ✅ Lulus | `npm run build` |
| Health check publik | ✅ HTTP 200 | `GET https://sbcc.my.id/up` |
| Halaman login publik | ✅ HTTP 200 | `GET https://sbcc.my.id/login` |
| Halaman tamu | ✅ HTTP 200 | `GET https://sbcc.my.id/cetak` |
| Redirect HTTP → HTTPS | ✅ HTTP 301 | `GET http://sbcc.my.id/login` → `https://sbcc.my.id/login` |
| Login via HTTPS | ✅ 302 → dashboard | Cookie sesi `Secure` + `SameSite=Lax` |
| Halaman admin via HTTPS | ✅ 200 | `/dashboard`, `/blangko`, `/blangko/5/preview` |
| PDF tamu (1 peserta) | ✅ 200, `application/pdf`, 508.228 byte, magic `%PDF-` | `/cetak/peserta/1/sertifikat` |
| PDF bulk (3 peserta) | ✅ 200, `application/pdf`, 509.207 byte, **3 halaman** | `/cetak/sertifikat/peserta?ids[]=1&ids[]=3&ids[]=2` |
| PDF bulk 50 peserta (template Blade) | ✅ **50 halaman**, 1 gambar latar, puncak memori ~100 MB (sebelum perbaikan >512 MB/OOM) | render lokal `contoh-sbcc` |
| Cetak admin (sertifikat & SK) | ✅ Link satuan & form bulk memakai `target="_blank"`; PDF terbuka di tab baru | `PesertaTest` |
| Nomor sertifikat admin dari `NULL` | ✅ Juara 1/2/3 & Pesilat Terbaik mendapat **nomor terakhir + 1**; label `Peserta` tetap `NULL` dan tetap bisa dicetak | `CetakSertifikatTest` |
| Sertifikat admin label `Peserta` (bulk) | ✅ **200**, `application/pdf`, **3 halaman**, 509.204 byte; ketiga `no_sertifikat` tetap `NULL` | endpoint IDs 713/715/730 |
| Pilihan background sertifikat (login) | ✅ Admin dapat memilih **Dengan/Tanpa background** untuk sertifikat peserta & official; default Dengan background; render lokal: tanpa background **1.423 byte / 0 image**, dengan background **508.234 byte / 1 image** | `CetakSertifikatTest`, `OfficialTest`, `BlangkoTest`; render `LayananCetak` peserta 257 |
| Background cetak tamu | ✅ Tamu **selalu** memakai background; `pakai_background=0` diabaikan (live: 508.234 byte, sama dengan request default) | `CetakPublikTest`; `/cetak/peserta/257/sertifikat?pakai_background=0` |
| Sertifikat sekolah (admin) | ✅ Daftar + tambah sekolah (nama unik), status/counter, PDF tanpa nomor, fallback `sert-sekolah.jpg`; operator/tamu ditolak; log `sertifikat_sekolah` tercatat | `SekolahTest` (11 test / 42 assertion) |
| Legalisir (admin) | ✅ Halaman pilih peserta/stempel/copy, CRUD stempel + validasi posisi, PDF hitam putih + 1 stempel, copy default 2 / setting 3, batas 50 peserta & 200 halaman; operator/tamu ditolak | `LegalisirTest` (21 test / 72 assertion) |
| Grayscale legalisir | ✅ Gambar berwarna menjadi R=G=B; tag stempel `data-stempel-legalisir` tidak diubah | `LegalisirTest` |
| Riwayat cetak legalisir | ✅ Jenis `legalisir`, tampilan `× N copy`, total halaman `jumlah_data × jumlah_copy` | `LegalisirTest` |
| Blangko jenis `sertifikat_sekolah` | ✅ Terpisah dari blangko peserta/SK; form blangko menerima jenis baru; preview sekolah memakai `NAMA SEKOLAH` | `SekolahTest`, `BlangkoTest` |
| Bulk template Blade aktif | ✅ 2 peserta → **2 halaman**; nama PESERTA SATU & PESERTA DUA tampil | uji lokal `BlangkoTest` + render PDF `contoh-sbcc` |
| SK bulk satu `no_sk` template Blade aktif | ✅ **2 halaman**; tiap halaman 1 peserta, nomor SK sama | uji lokal `BlangkoTest` + render PDF `contoh-sk-sbcc` |
| SK bulk dua `no_sk` template Blade aktif | ✅ **2 halaman**; tiap halaman 1 peserta, nomor SK sesuai | uji lokal + render PDF `contoh-sk-sbcc` |
| SK bulk admin tanpa `no_sk` | ✅ **2 halaman**; tiap halaman 1 peserta, nomor kosong | `CetakSkTest`, `BlangkoTest` |
| PDF SK tamu tanpa nomor | ✅ 200, `application/pdf`, 240.314 byte, **1 halaman / 1 baris**, nomor SK kosong; format Nama/Juara/Pelaksanaan/Tempat tampil | `/cetak/peserta/259/sk` |
| Tampilan `/cetak` di ponsel | ✅ 320/375 px: 7 kolom, tabel dapat digeser, badge Juara 1/2/3, Peserta, Pesilat Terbaik tampil | Chrome DevTools iframe |
| Nomor sertifikat label Peserta | ✅ **0** dari 354 baris `PESERTA` masih punya nomor | Migrasi `2026_10_23_000009` + query DB |
| Sertifikat label Peserta | ✅ 200, `application/pdf`; tombol Sertifikat tampil; `no_sertifikat` tetap `NULL` | `/cetak/peserta/2246/sertifikat` |
| Riwayat cetak memakai WIB | ✅ Log baru `23-10-2026 13:18:01` WIB; log lama digeser +7 jam | `/log-cetak` + migrasi `2026_10_23_000010` |
| Cetak tamu juara dari `NULL` | ✅ 200, `application/pdf`, 508.242 byte; nomor baru `1815` (MAX+1) | `/cetak/peserta/260/sertifikat` |
| Akses admin tanpa login | ✅ 302 → `/login` | `/dashboard` tanpa cookie |
| Route `/sekolah` tanpa login | ✅ 302 → `/login` | live `https://sbcc.my.id/sekolah`, 27 Oktober 2026 |
| Koneksi tunnel | ✅ 4 koneksi terdaftar | `cloudflared tunnel list` |
| Sertifikat TLS | ✅ `CN=sbcc.my.id` (Let's Encrypt via Cloudflare) | Pengecekan sertifikat HTTPS |
| Script start/stop | ✅ Berfungsi | `tools/p5-start-named.ps1`, `tools/p5-stop.ps1` |

Sesi validasi live terakhir: **28 Oktober 2026**, URL `https://sbcc.my.id`; SK tamu peserta 259 = **1 halaman** dengan format Nama/Juara/Pelaksanaan/Tempat; uji template Blade lokal: **25 Oktober 2026**; pilihan background sertifikat admin & pemaksaan background untuk tamu: **25 Oktober 2026**; fitur sertifikat sekolah: **27 Oktober 2026** (otomatis; uji live setelah migrasi); fitur legalisir: **28 Oktober 2026** (otomatis; migrasi DB aplikasi sudah dijalankan, uji cetak fisik stempel masih menunggu).

Konfigurasi produksi yang dipakai **hanya untuk proses server** (`.env` tidak diubah):

```powershell
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
APP_URL=https://sbcc.my.id
LOG_LEVEL=warning
```

---

## 2. Checklist UAT Fungsional (TRD §12)

Legenda: ✅ = lulus (otomatis/terverifikasi), ⏳ = menunggu uji manual, ⚠️ = perlu tindakan.

| No | Kasus uji | Harapan | Status | Catatan |
|---|---|---|---|---|
| UT-01 | Login salah 6× | Terkunci sementara (429) | ✅ | `AuthTest` (rate limit login) |
| UT-02 | CRUD kontingen + peserta + official | Tersimpan, validasi tampil | ✅ | `KontingenTest`, `PesertaTest`, `OfficialTest` |
| UT-03 | Cetak 1, 3, dan 50 peserta | Jumlah halaman = jumlah peserta, urut | ✅ 1 & 3 / ⏳ 50 fisik | 3 peserta = 3 halaman (diverifikasi via `/Type /Page`); 50 peserta diuji otomatis |
| UT-04 | SK batch 10 juara, `no_sk` homogen | 10 halaman; tiap halaman 1 peserta, nomor SK sama | ✅ | `CetakSkTest` |
| UT-05 | SK **bulk admin** campur dua `no_sk` / ada yang kosong | 2 peserta = 2 halaman; nomor kosong tetap tercetak kosong | ✅ | `CetakSkTest`, `BlangkoTest` |
| UT-05b | Cetak ulang peserta bernomor | Nomor sama, tidak generate baru | ✅ | `CetakSertifikatTest` |
| UT-05c | Cetak paralel 2 operator | Tidak ada nomor ganda | ⏳ | Perlu 2 operator/2 browser; logika `GET_LOCK` + UNIQUE sudah ada |
| UT-05d | SK tamu perorangan tanpa `no_sk` | PDF tercetak 1 halaman / 1 baris, nomor kosong | ✅ | Live `/cetak/peserta/259/sk` + `CetakPublikTest` |
| UT-05e | Sertifikat untuk label **Peserta** | PDF tercetak, tombol tampil, nomor tetap `NULL` | ✅ | `CetakPublikTest`, live `/cetak/peserta/2246/sertifikat` |
| UT-05f | Cetak tamu juara dengan `no_sertifikat` `NULL` | PDF tercetak, nomor `MAX+1` (live: `1815`) | ✅ | `CetakPublikTest`, live `/cetak/peserta/260/sertifikat` |
| UT-05g | Bulk sertifikat memakai template Blade aktif | 2 peserta = 2 halaman, semua nama tampil | ✅ | `BlangkoTest` + render PDF `contoh-sbcc` |
| UT-05h | SK bulk template Blade aktif | 2 peserta = 2 halaman; tiap halaman 1 peserta | ✅ | `BlangkoTest` + render PDF `contoh-sk-sbcc` |
| UT-05i | Pilihan background sertifikat saat login | Admin dapat memilih Dengan/Tanpa background; tamu selalu Dengan background | ✅ | `CetakSertifikatTest`, `OfficialTest`, `CetakPublikTest`, `BlangkoTest` |
| UT-06 | Akses PDF tanpa login | Redirect ke login | ✅ | `/dashboard` tanpa cookie → 302 |
| UT-07 | Cetak official (nama + kontingen) | Data benar di PDF | ✅ | `OfficialTest` |
| UT-08 | Upload logo/background salah format | Ditolak dengan pesan | ✅ | `PengaturanTest` |
| UT-09 | Akses HTTPS dari HP di luar jaringan | Login normal, aset HTTPS, cookie Secure | ⏳ | Sudah diuji dari mesin ini lewat `https://sbcc.my.id`; **perlu 1 HP dengan data seluler** |
| UT-10 | Hapus kontingen | Peserta/official ikut terhapus | ✅ | `KontingenTest` (cascade) |
| UT-11 | Sertifikat sekolah: admin tambah sekolah & cetak | Sekolah tersimpan (nama unik), PDF tanpa nomor, status/counter & log tercatat; operator/tamu ditolak | ✅ | `SekolahTest` (11 test / 42 assertion) |
| UT-12 | Legalisir 2 peserta, copy default 2 lalu copy 3 | PDF hitam putih + 1 stempel berwarna; halaman = peserta valid × copy (4 lalu 6); log `legalisir` + `jumlah_copy`; stempel nonaktif/gambar hilang/posisi di luar halaman ditolak; operator/tamu ditolak | ✅ otomatis / ⏳ fisik | `LegalisirTest` (21 test / 72 assertion) |

---

## 3. Checklist Khusus P5

| No | Pemeriksaan | Status | Cara / Bukti |
|---|---|---|---|
| P5-01 | Aplikasi dapat diakses publik via HTTPS | ✅ | Named Tunnel `https://sbcc.my.id`; `/up`, `/login`, `/cetak` = 200 |
| P5-02 | Cookie sesi `Secure` + `SameSite=Lax` | ✅ | Respons login di HTTPS |
| P5-03 | Redirect & URL generated memakai HTTPS | ✅ | HTTP → 301 HTTPS (middleware `ForceHttps`); `trustProxies('*')` aktif |
| P5-04 | `APP_DEBUG=false` pada proses publik | ✅ | Env process saat start; bot scan `/.env.dev` ditolak tanpa bocor stack trace |
| P5-05 | Rate limit tamu 30 PDF/jam/IP | ✅ | Test otomatis request ke-31 → 429 |
| P5-06 | Login lokal `http://localhost:8000` | ⚠️ | **Tidak bisa** saat `SESSION_SECURE_COOKIE=true` (cookie hanya dikirim via HTTPS). Gunakan URL HTTPS, atau hentikan P5 untuk mode lokal |
| P5-07 | Start/stop deployment dapat diulang | ✅ | `tools/p5-start-named.ps1` → domain tetap; `tools/p5-stop.ps1` → port & tunnel berhenti |
| P5-08 | Cetak fisik A4 & F4/Folio | ⏳ | **Perlu printer + kertas blanko asli**; cek margin, skala 100%, tidak terpotong |
| P5-09 | Cek typo 100 dokumen | ⏳ | Sampel 100 PDF (nama, kategori, nomor, tanggal, tanda tangan) |
| P5-10 | Alignment tanda tangan & stempel | ⏳ | Cetak 1 sertifikat + 1 SK, ukur posisi blok TTD; **legalisir:** cetak 1 sertifikat, pastikan stempel tidak terpotong dan posisinya sesuai preview mm |
| P5-11 | Backup & restore database | ⏳ | `mysqldump` + restore ke DB uji; belum dijalankan di P5 |
| P5-12 | Named Tunnel + domain tetap | ✅ | `https://sbcc.my.id` (tunnel `sertifikat`, 4 koneksi, TLS valid) |
| P5-13 | Cloudflare Access / WAF untuk area admin | ⏳ | **Belum diaktifkan**; disarankan sebelum URL dibagikan luas. Halaman `/cetak` harus tetap publik |
| P5-14 | Autostart setelah reboot | ⏳ | Saat ini proses dijalankan manual lewat script; service/Task Scheduler belum dipasang |
| P5-15 | Legalisir hitam putih + stempel | ✅ otomatis / ⏳ fisik | `LegalisirTest`: 2 copy = 2 halaman, 2 peserta × 3 copy = 6 halaman, grayscale R=G=B, stempel berwarna; cetak fisik & posisi stempel menunggu printer |

---

## 4. Runbook Singkat

### 4.1 Menyalakan Named Tunnel (produksi)

```powershell
# Dari root proyek
powershell -ExecutionPolicy Bypass -File tools/p5-start-named.ps1
```

Script akan:

1. membersihkan cache config/route/view;
2. menghentikan instance P5 sebelumnya (Quick/Named);
3. menjalankan `php artisan serve` dengan env produksi (`APP_URL=https://sbcc.my.id`);
4. menjalankan `cloudflared tunnel --config tools/cloudflared-named.yml run sertifikat`;
5. menyimpan URL + PID ke `storage/app/p5-named.json`;
6. menunggu `GET https://sbcc.my.id/up` = 200.

### 4.2 Menyalakan Quick Tunnel (fallback)

```powershell
powershell -ExecutionPolicy Bypass -File tools/p5-start.ps1
```

URL `*.trycloudflare.com` acak dan berubah setiap restart — hanya untuk UAT cepat.

### 4.3 Mematikan

```powershell
powershell -ExecutionPolicy Bypass -File tools/p5-stop.ps1
```

### 4.4 Log

| Log | Path |
|---|---|
| Laravel server | `storage/logs/serve.out.log`, `storage/logs/serve.err.log` |
| Named Tunnel | `storage/logs/cloudflared-named.out.log`, `storage/logs/cloudflared-named.err.log` |
| Quick Tunnel | `storage/logs/cloudflared.out.log`, `storage/logs/cloudflared.err.log` |
| Aplikasi | `storage/logs/laravel.log` |

### 4.5 Kembali ke mode lokal

`p5-stop.ps1` menghentikan proses produksi. `.env` tetap `APP_ENV=local` / `APP_DEBUG=true`, jadi `php artisan serve` biasa langsung kembali ke mode pengembangan (cookie tidak lagi `Secure`).

---

## 5. Detail Named Tunnel (selesai)

| Item | Nilai |
|---|---|
| Domain | `sbcc.my.id` (apex) |
| Tunnel | `sertifikat` |
| UUID | `30c4d639-b76a-44d5-be62-89d4a39384bd` |
| Config | `tools/cloudflared-named.yml` |
| Credentials | `C:\Users\Lenovo\.cloudflared\30c4d639-b76a-44d5-be62-89d4a39384bd.json` |
| Nameserver | `olof.ns.cloudflare.com`, `rayne.ns.cloudflare.com` |
| DNS | CNAME `sbcc.my.id` → tunnel |

### 5.1 Langkah lanjutan yang disarankan

1. Aktifkan **Always Use HTTPS** di Cloudflare Dashboard (pelengkap middleware `ForceHttps`).
2. Aktifkan **Cloudflare Access** (email OTP) khusus area admin: `/dashboard`, `/peserta`, `/blangko`, `/pengaturan`, `/user`, `/log-cetak`, `/legalisir`, `/legalisir/stempel`. Halaman `/cetak` tetap publik.
3. Pasang autostart (Windows Service untuk `cloudflared` + Laragon/Apache sebagai service) agar siap setelah reboot.
4. Uji ulang UT-09 dari HP di luar jaringan.
5. Lakukan UAT cetak fisik (P5-08..P5-10) dan backup/restore (P5-11).

---

## 6. Catatan Keamanan

- Aplikasi sudah publik di `https://sbcc.my.id`. **Jangan bagikan tautan admin** ke grup umum; kirim hanya ke panitia.
- Password akun seed **sudah diganti** oleh pemilik aplikasi. Jangan mengembalikan ke `password`.
- Jangan mengaktifkan `APP_DEBUG=true` saat URL publik aktif.
- Bot otomatis sudah mulai memindai path sensitif (`/.env.dev`, `/config/.env`, dll). Pastikan `APP_DEBUG=false` dan jangan simpan kredensial di file yang dapat diakses web.
- Aktifkan **Cloudflare Access** untuk area admin sebelum event.
- Hentikan tunnel setelah UAT selesai (`tools/p5-stop.ps1`).

---

## 7. Tanda Tangan UAT

| Peran | Nama | Tanggal | Tanda tangan |
|---|---|---|---|
| Operator/Panitia | | | |
| Developer | | | |
| Penanggung jawab data | | | |
