# TRD — Sistem Cetak Sertifikat Kejuaraan (SCS)

| Field | Nilai |
|---|---|
| Versi | 1.6 |
| Tanggal | 28 Oktober 2026 |
| Prasyarat Bagi | `PRD.md` |
| Dokumen Terkait | `INDEX.md`, `DB_SCHEMA.md`, `LEGALISIR_PLAN.md` |

---

## 1. Arsitektur

**Monolithic MVC, session-based, BUKAN API.** Satu aplikasi Laravel melayani HTML (Blade + Tailwind) dan PDF (view khusus) lewat controller yang sama.

```mermaid
flowchart LR
    subgraph Client
        B1[Browser panitia - Blade + Tailwind]
        B2[Browser: PDF viewer tab]
    end
    subgraph Cloudflare
        CF[Cloudflare Tunnel / cloudflared]
    end
    subgraph PC Panitia (Windows)
        W[Web: Apache/XAMPP atau php artisan serve - port 8000]
        PHP[Laravel App - PHP 8.2+]
    end
    DB[(MySQL/MariaDB lokal)]
    B1 -->|HTTPS| CF --> W --> PHP
    B2 -->|HTTPS| CF --> W --> PHP
    PHP --> DB
    PHP --> FS[(storage/app/public: logo, background)]
```

**Keputusan arsitektur penting:**

| Keputusan | Alasan |
|---|---|
| Monolit tanpa API | Komunikasi server-rendered; fitur internal saja; deployment 1 mesin |
| Session auth bawaan Laravel | Tidak perlu token/JWT; semua client adalah browser |
| PDF via `barryvdh/laravel-dompdf` | Pure PHP (tanpa binary eksternal seperti wkhtmltopdf) → mudah di Windows; orientasi landscape tersedia |
| Blade PDF **tanpa Tailwind** | DOMPDF tidak memahami Tailwind 4 (CSS modern). View PDF memakai CSS inline/plain yang aman DOMPDF |
| Tanpa queue | Cetak dilakukan sinkron (stream langsung). Jumlah data kecil |
| DB lokal MySQL | Tidak perlu cloud DB; tunnel hanya membuka HTTP |

---

## 2. Tech Stack & Versi

| Komponen | Pilihan | Catatan |
|---|---|---|
| Bahasa | PHP ≥ 8.3 | Terpasang: **PHP 8.3.29 (ZTS)**; ekstensi `zip` tidak ada → Composer memakai `unzip.exe` milik Laragon (fallback otomatis) |
| Framework | **Laravel 13.17** | Awal dokumen menyebut 12.x, namun `composer create-project laravel/laravel` memasang **13.17** (versi terbaru) — dipakai apa adanya |
| Database | MySQL 8.x | Terpasang: **MySQL 8.0.30** via Laragon (`D:\Project\laragon`); engine InnoDB, utf8mb4 |
| Frontend | Blade + Tailwind CSS 4 via **Vite** | `npm run build` wajib dijalankan sebelum `php artisan test`/deploy (layout memakai `@vite`) |
| Font | Bunny "Instrument Sans" (di-bundle lokal oleh plugin Vite) | Sudah ter-*bundle* ke `public/build/assets` → **tetap tampil saat venue tanpa internet** |
| PDF | `barryvdh/laravel-dompdf` ^3.1 | Wrapper `dompdf/dompdf`; terverifikasi render 1 halaman A4 landscape |
| Auth | Laravel session auth (manual scaffold) | Tanpa Breeze/Jetstream agar tetap ringan; rate limit percobaan login manual (5×/menit) |
| Icon | MDI / Heroicons inline SVG | Tanpa CDN berat |
| Web server lokal | **Laragon** atau XAMPP (Apache) — alternatif `php artisan serve` | Laragon sudah terpasang di mesin ini |
| Tunnel | `cloudflared` (Cloudflare Tunnel) | Terpasang lokal di `tools/cloudflared.exe`; **Named Tunnel `sbcc.my.id` aktif**, Quick Tunnel sebagai fallback |

### 2.1 Perintah Scaffolding

```bash
composer create-project laravel/laravel sistem-cetak-sertifikat
cd sistem-cetak-sertifikat
composer require barryvdh/laravel-dompdf
npm install
npm run dev          # dev; npm run build untuk produksi
php artisan storage:link
php artisan migrate --seed
```

`.env` inti:

```env
APP_NAME="Sistem Cetak Sertifikat"
APP_ENV=production
APP_URL=http://localhost:8000      # diganti https://sertifikat.sudomain.cf nanti
APP_TIMEZONE=Asia/Jakarta          # WIB; dipakai untuk timestamp log cetak
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=sistem_cetak_sertifikat
DB_USERNAME=root
DB_PASSWORD=...

TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true        # saat sudah di-balik tunnel
```

> **Timezone:** aplikasi memakai `Asia/Jakarta` (WIB). `print_logs.created_at` lama yang tersimpan dalam UTC digeser +7 jam oleh migrasi `2026_10_23_000010`; log baru langsung memakai WIB.

---

## 3. Struktur Proyek

```
app/
 ├ Http/
 └ Services/
     └ NomorSertifikat.php        ← lazy assignment nomor sertifikat (MAX+1, reuse)
    ├ Controllers/
    │   ├ Auth/ (LoginController, LogoutController)
    │   ├ DashboardController.php
    │   ├ KontingenController.php
    │   ├ PesertaController.php
    │   ├ OfficialController.php
    │   ├ CetakController.php        ← endpoint PDF admin
    │   ├ LegalisirController.php    ← pilih peserta, cetak legalisir, CRUD stempel
    │   ├ PengaturanController.php
    │   └ UserController.php
    ├ Models/ (User, Kontingen, Peserta, Official, PrintLog, Setting, StempelLegalisir)
    ├ Requests/ (KontingenRequest, PesertaRequest, OfficialRequest, StempelLegalisirRequest, ...)
    ├ Services/ (NomorSertifikat, BlangkoRenderer, LegalisirRenderer, LayananCetak)
    └ Middleware/
resources/
 ├ views/
 │   ├ layouts/app.blade.php        ← shell Tailwind + sidebar
 │   ├ web/
 │   │   ├ dashboard, kontingen/, peserta/, official/, pengaturan/, auth/login, log/
 │   │   └ legalisir/{index,stempel/index,stempel/form}.blade.php
 │   └ pdf/                          ← NAMESPACE PDF (tanpa Tailwind!)
 │       ├ layouts/base-pdf.blade.php
 │       ├ sertifikat-peserta.blade.php  ← mendukung mode legalisir + stempel
 │       ├ sk-juara.blade.php
 │       └ sertifikat-official.blade.php
routes/web.php
config/dompdf.php (tergenerate)
public/storage → storage/app/public (symlink)
```

**Aturan tegas:** semua file di `resources/views/pdf/**` hanya boleh memakai CSS yang didukung DOMPDF (inline-style, `@page`, tag HTML 2.1-ish). Jangan nyempil Tailwind ke sana.

---

## 4. Desain Data

Skema lengkap (DDL, index, ERD): lihat **`DB_SCHEMA.md`**.

Ringkas entitas: `users`, `kontingens`, `pesertas`, `officials`, `settings`, `print_logs`, `blangkos`, `blangko_elemen`, `teks_varians`, `sekolahs`, `stempel_legalisirs`.

Model & relasi ringkas:

```php
Kontingen hasMany Peserta; hasMany Official;
Peserta belongsTo Kontingen;
Official belongsTo Kontingen;
Setting  = key-value (setting_key TEXT value) — helper Setting::get('nama_kejuaraan');
PrintLog polymorphic-lite (kolom peserta_id/official_id nullable + jenis_dokumen VARCHAR, bukan ENUM);
Peserta.no_sertifikat = lazy MAX+1 saat cetak pertama (admin atau tamu `/cetak`) untuk Juara 1/2/3 & Pesilat Terbaik, dipakai ulang bila sudah ada; label Peserta tetap NULL; no_sk = input manual;
Sekolah = nama unik + terakhir_dicetak_at + jumlah_cetak; tidak punya nomor sertifikat;
StempelLegalisir = nama unik + gambar + posisi/ukuran mm + aktif; satu desain = satu posisi tetap;
PrintLog.jumlah_copy = copy per sertifikat; total halaman = jumlah_data × jumlah_copy;
User role: 'admin' | 'operator'  (gate `can('admin')` → gate `admin-only`)
```

---

## 5. Routing & Endpoint

Semua route di belakang middleware `auth` (grup), kecuali login. Tanpa prefix `/api`.

| Method | URI | Nama Route | Controller@method | Keterangan |
|---|---|---|---|---|
| GET | `/login` | `login.show` | Auth\LoginController@show | |
| POST | `/login` | `login.attempt` | Auth\LoginController@attempt | rate-limit 5/menit |
| POST | `/logout` | `logout` | Auth\LogoutController | |
| GET | `/` | `dashboard` | DashboardController | user login: dashboard; tamu: redirect ke `/cetak` |
| GET/POST | `/kontingen` | `kontingen.index/create` | KontingenController | |
| GET/PUT/DELETE | `/kontingen/{id}` | `kontingen.edit/update/destroy` | KontingenController | destroy: konfirmasi + cascade |
| GET | `/peserta` | `peserta.index` | PesertaController | query: `q, kontingen_id, kategori, juara, page` |
| GET/POST | `/peserta/create` | `peserta.create/store` | PesertaController | |
| GET/PUT/DELETE | `/peserta/{id}` | `peserta.edit/update/destroy` | PesertaController | |
| GET | `/cetak/sertifikat/peserta?ids[]=..` | `cetak.peserta.sertifikat` | CetakController@sertifikatPeserta | stream PDF landscape; `ids[]` dari checkbox form GET (1–50) **atau `ids=` tunggal** dari tombol per-baris; nomor sertifikat lazy-assign; `pakai_background=0` (opsional, admin) untuk cetak tanpa background |
| GET | `/cetak/sk?ids[]=..` | `cetak.peserta.sk` | CetakController@skJuara | stream PDF portrait; nomor SK dibaca dari `pesertas.no_sk`; satu peserta = satu halaman |
| GET | `/official` | `official.index` | OfficialController | daftar + form input |
| POST | `/official` | `official.store` | OfficialController | simpan + redirect untuk preview/cetak |
| GET | `/cetak/sertifikat/official/{id}` | `cetak.official.sertifikat` | CetakController@sertifikatOfficial | stream PDF; `pakai_background=0` (opsional, admin) untuk cetak tanpa background |
| DELETE | `/official/{id}` | `official.destroy` | OfficialController | |
| GET | `/pengaturan` | `pengaturan.index` | PengaturanController (admin-only) | upload logo/background, template teks |
| PUT | `/pengaturan` | `pengaturan.update` | PengaturanController (admin-only) | |
| GET | `/log-cetak` | `log-cetak.index` | LogCetakController (admin-only) | filter tanggal, jenis (termasuk `sertifikat_sekolah` & `legalisir`); total halaman = `jumlah_data × jumlah_copy` |
| GET/POST | `/user` `/user/{id}` | `user.*` | UserController (admin-only) | kelola akun operator |
| GET | `/sekolah` | `sekolah.index` | SekolahController@index (admin-only) | daftar sekolah + status cetak + pencarian `q` |
| POST | `/sekolah` | `sekolah.store` | SekolahController@store (admin-only) | tambah sekolah (nama saja, unik) |
| GET | `/cetak/sertifikat/sekolah/{sekolah}` | `cetak.sekolah.sertifikat` | CetakController@sertifikatSekolah (admin-only) | stream PDF ucapan terima kasih; **tanpa nomor sertifikat**; memakai blangko `sertifikat_sekolah` atau fallback |
| GET | `/legalisir?ids[]=..` | `legalisir.index` | LegalisirController@index (admin-only) | halaman pilih peserta, satu stempel aktif, copy, total halaman; `ids` dibatasi 50 |
| GET | `/legalisir/cetak?ids[]=..&stempel_id=..` | `legalisir.cetak` | LegalisirController@cetak (admin-only) | stream PDF **hitam putih** + 1 stempel; copy dari setting; batas 200 halaman; log `legalisir` |
| GET | `/legalisir/stempel` | `legalisir.stempel.index` | LegalisirController@stempelIndex (admin-only) | daftar desain stempel + status aktif + posisi |
| GET | `/legalisir/stempel/create` | `legalisir.stempel.create` | LegalisirController@stempelCreate (admin-only) | form stempel + preview posisi |
| POST | `/legalisir/stempel` | `legalisir.stempel.store` | LegalisirController@stempelStore (admin-only) | upload gambar + simpan posisi |
| GET | `/legalisir/stempel/{stempel}/edit` | `legalisir.stempel.edit` | LegalisirController@stempelEdit (admin-only) | edit stempel |
| PUT | `/legalisir/stempel/{stempel}` | `legalisir.stempel.update` | LegalisirController@stempelUpdate (admin-only) | perbarui stempel |
| DELETE | `/legalisir/stempel/{stempel}` | `legalisir.stempel.destroy` | LegalisirController@stempelDestroy (admin-only) | hapus stempel |

**Multi-select tanpa JS framework:** Tabel di `peserta.index` dibungkus `<form method="GET" action="cetak">` dengan checkbox `name="ids[]"`. Tombol submit ganda: `formaction="cetak/sertifikat/peserta"` dan `formaction="cetak/sk"`. Vanilla JS kecil hanya untuk tombol "Select All", konfirmasi `confirm()`, dan menambahkan pilihan `pakai_background` ke tautan cetak per-baris. Selain itu, tiap baris punya tombol "Cetak" yang mengarah ke endpoint yang sama dengan `ids={id}` (mode satuan) — jadi **satuan dan bulk memakai satu path kode yang sama**, hanya beda jumlah id.

> ⚠️ **Gotcha Laravel 13 — resource route untuk "peserta":** `Str::singular('peserta')` menghasilkan **`pesertum`** (aturan inflector Latin `-a` → `-um`, sama seperti `data` → `datum`). Akibatnya `Route::resource('peserta', …)` membuat parameter `{pesertum}` yang **tidak cocok** dengan variabel `$peserta` di controller, sehingga route model binding gagal **tanpa error** (controller menerima model kosong). Wajib dipaksa:
> ```php
> Route::resource('peserta', PesertaController::class)
>     ->parameters(['peserta' => 'peserta'])
>     ->except(['show']);
> ```
> Selalu verifikasi dengan `php artisan route:list` setelah menambah resource route. (`kontingen` tidak bermasalah: `Str::singular('kontingen')` = `kontingen`.)

---

## 6. Desain Fitur Kunci

### 6.1 Autentikasi & Otorisasi

- Login email+password, `session` file driver (lokal), regenerasi session id saat login (`$request->session()->regenerate()`).
- Rate limit: `RateLimiter::for('login', 5 per minute by IP+email)`.
- Middleware kecil `EnsureAdmin` → untuk route `/pengaturan`, `/log`, `/user`, `/blangko`, `/sekolah` (termasuk cetak sertifikat sekolah), dan `/legalisir` (termasuk cetak & CRUD stempel).
- Gate: `Gate::define('admin', fn ($user) => $user->role === 'admin');`

### 6.2 Daftar Peserta + Filter (FR-05)

Query:

```php
$peserta = Peserta::query()
  ->join('kontingens','kontingens.id','=','pesertas.kontingen_id')
  ->when($request->q,          fn($q,$v)=>$q->where('pesertas.nama','like',"%$v%"))
  ->when($request->kontingen_id, fn($q,$v)=>$q->where('pesertas.kontingen_id',$v))
  ->when($request->kategori,   fn($q,$v)=>$q->where('pesertas.kategori',$v))
  ->when($request->juara !== null && $request->juara !== '',
         fn($q,$v)=>$v==='-' ? $q->whereNull('juara') : $q->where('juara',$v))
  ->orderBy('pesertas.kategori')->orderBy('pesertas.juara')->orderBy('pesertas.nama')
  ->paginate(25)->withQueryString();
```

Badge juara: nilai lama `1`/`2`/`3` → emas/silver/perunggu; teks lain (mis. `Peserta`, `Pesilat Terbaik`) → badge netral (Tailwind class map).

### 6.3 PDF Sertifikat Peserta (FR-06)

- Controller menerima `ids[]` → validasi ≤ 50 → ambil peserta + relasi.
- View `pdf/sertifikat-peserta.blade.php` di-loop `@foreach`: setiap peserta `div` dengan `page-break-after: always`.
- `Pdf::loadView(...)->setPaper('a4','landscape')->stream('sertifikat-juara.pdf');`
- Layout: background image full-bleed (A4 landscape 297×210 mm `297mm 210mm` img), teks nama & data di-absolute (CSS `position:relative` + `top/left` dalam mm) — posisi ternyata konsisten di DOMPDF.
- Data di-render dari `Setting` template: `teks_sertifikat` dengan placeholder `{NAMA} {NOMOR_SERTIFIKAT} {JUARA_ANGKA} {JUARA_ROMAWI} {KATEGORI} {KELAS} {KONTINGEN}` → `str_replace()`; default tersedia saat seed.

Potongan controller:

```php
public function sertifikatPeserta(Request $request)
{
    $data = $request->validate(['ids' => ['required','array','min:1','max:50']]);
    $pesertas = Peserta::with('kontingen')->whereIn('id', $data['ids'])
        ->whereNotNull('juara')->get();

    abort_if($pesertas->isEmpty(), 422, 'Peserta terpilih belum memiliki juara.');

    // Penomoran lazy: yang belum punya nomor di-assign, yang sudah dipakai ulang
    NomorSertifikat::assign($pesertas);

    $pdf = Pdf::loadView('pdf.sertifikat-peserta', ['pesertas' => $pesertas])
              ->setPaper('a4', 'landscape');

    PrintLog::create([
        'user_id' => auth()->id(),
        'jenis_dokumen' => 'sertifikat_peserta',
        'jumlah_data' => $pesertas->count(),
        'konteks' => $pesertas->pluck('nama')->implode(', '),
    ]);

    return $pdf->stream("sertifikat-juara-".now()->format('Ymd-His').".pdf");
}
```

### 6.4 PDF SK Juara (FR-07)

- `pdf/sk-juara.blade.php` A4 **portrait**; struktur: kop (logo + identitas kejuaraan), judul "SURAT KEPUTUSAN PANITIA TENTANG PENETAPAN PESERTA JUARA", nomor SK dibaca dari kolom `pesertas.no_sk` (hasil input manual panitia), "Menimbang / Mengingat / Memutuskan / Ditetapkan di…" (struktur teks di view, konten dari settings), tabel: No | Nama | Kontingen | Kategori | Kelas | Juara, blok penandatangan.
- **Aturan SK:** **satu peserta = satu halaman SK**. `no_sk` hanya label pada halaman, bukan pengelompokan; nomor yang sama tetap menghasilkan halaman terpisah per peserta, dan nomor kosong tetap dicetak kosong. Cetak bulk admin dengan N peserta menghasilkan N halaman; halaman tamu selalu satu peserta. Nomor berbeda/kosong tidak membatalkan cetakan.
- Peserta dipilih dikelompokkan: `->sortBy([['kategori','asc'],['juara','asc']])`.
- CSS `@page { size: A4 portrait; margin: 15mm 20mm; }`; tabel memakai `border-collapse` sederhana.

### 6.5 Penomoran Sertifikat (lazy assignment)

Nomor sertifikat **tidak memakai tabel counter** — sumber kebenaran adalah kolom `pesertas.no_sertifikat` sendiri, sehingga nomor terikat permanen pada baris peserta:

- **Belum punya nomor** → saat pertama kali dicetak (admin atau tamu `/cetak`), sistem mengambil `MAX(no_sertifikat)` + 1 (untuk batch admin: diberi berurutan 1..n sesuai urutan pilihan). Hanya Juara 1/2/3 dan Pesilat Terbaik; label `Peserta` tidak memicu alokasi.
- **Sudah punya nomor** → dipakai ulang apa adanya; cetak ulang 10× nomornya tetap.
- **Anti-bentrok saat operator paralel** → named lock MySQL (`GET_LOCK`) instead of tabel counter; kolom tetap diberikan `UNIQUE` sebagai jaring pengaman.

```php
// app/Services/NomorSertifikat.php
public static function assign($pesertas): void
{
    DB::transaction(function () use ($pesertas) {
        DB::select("SELECT GET_LOCK('scs_no_sertifikat', 10) AS l");
        try {
            $next = (int) Peserta::whereNotNull('no_sertifikat')->max('no_sertifikat');
            foreach ($pesertas as $p) {
                if ($p->no_sertifikat !== null) continue;   // sudah ada → reuse
                $p->no_sertifikat = ++$next;                // assign & simpan
                $p->save();
            }
        } finally {
            DB::select("SELECT RELEASE_LOCK('scs_no_sertifikat')");
        }
    });
}
```

- `no_sertifikat` disimpan **numerik** (INT UNSIGNED, UNIQUE) agar `MAX()` benar secara angka; batas penampilan (zero-prefix, dsb.) cukup diterapkan di view: `str_pad($no, 3, '0', STR_PAD_LEFT)`.
- **Nomor SK** berbeda: kolom `pesertas.no_sk` VARCHAR diisi **manual oleh panitia** di form peserta (nomor dokumen resmi dari buku agenda); cetak SK hanya membacanya — tidak digenerate. Template contoh `contoh-sk-sbcc` saat ini **tidak menampilkan baris nomor**; `no_sk` tetap tersimpan dan tercatat di log.

> **Implementasi aktual (P2):** `app/Services/NomorSertifikat.php` — method `assign(iterable $peserta)`. Hanya baris dengan `no_sertifikat === null` **dan** berhak (`Peserta::berhakNomorSertifikat()`: Juara 1/2/3 atau Pesilat Terbaik) yang di-assign; sisanya dilewati (reuse). Dipanggil dari `CetakController::sertifikatPeserta()` (admin) dan `CetakPublikController::sertifikat()` (tamu). Kedua alur **boleh mencetak label Peserta**, tetapi label **Peserta** tidak pernah mendapat nomor; model juga mengosongkan `no_sertifikat` saat `juara` diubah menjadi Peserta.

### 6.6 Sertifikat Official (FR-08)

- Halaman daftar official + **form inline** (nama, kontingen `@select` dari `$kontingens`, jabatan) → POST simpan → redirect ke halaman yang sama dengan tombol **Cetak** per baris + banner tombol "Cetak" yang muncul.
- `pdf/sertifikat-official.blade.php` serupa sertifikat peserta, memakai `teks_sertifikat_official` dengan placeholder `{NAMA} {JABATAN} {KONTINGEN}`.

### 6.7 Pengaturan Dokumen (FR-10)

- Store di tabel `settings` (key-value). Form satu halaman tiga blok: Identitas, Tanda Tangan, Template & Layout.
- Upload: `$request->file(...)->store('publik/dokumen','public')` → simpan path di setting. Gabung dengan `asset('storage/...')` untuk preview Blade; di PDF pakai `public_path('storage/...')` (DOMPDF butuh path filesystem).
- Validasi: `image|mimes:jpg,jpeg,png,webp|max:2048`.

---

## 6.8 Cetak Guest (tanpa login)

Route publik dilindungi **dua lapis rate limiter** (didefinisikan di `AppServiceProvider`):

| Limiter | Batas | Dipakai pada |
|---|---|---|
| `publik` | 60 permintaan/menit per IP | Halaman daftar peserta & formulir official |
| `cetak-publik` | **30 PDF/jam per IP** | Unduh sertifikat/SK/official |

| Method | URI | Nama Route | Keterangan |
|---|---|---|---|
| GET | `/cetak` | `publik.index` | Daftar seluruh peserta, paginasi **15 per halaman**, pencarian nama (`?q=`) |
| GET | `/cetak/official` | `publik.official` | Formulir nama + dropdown kontingen + jabatan |
| GET | `/cetak/peserta/{peserta}/sertifikat` | `publik.sertifikat` | PDF sertifikat (bila peserta punya keterangan juara, termasuk `Peserta`); Juara 1/2/3 & Pesilat Terbaik mendapat nomor `MAX+1`; label `Peserta` tetap `NULL` |
| GET | `/cetak/peserta/{peserta}/sk` | `publik.sk` | PDF SK perorangan; satu peserta = satu halaman; nomor SK boleh kosong |
| POST | `/cetak/official` | `publik.official.cetak` | PDF sertifikat coach/official (tidak disimpan ke DB) |

- Log cetak tamu ditulis dengan `user_id = NULL` dan awalan `[TAMU]` pada kolom `konteks` → tetap terlihat di Riwayat Cetak.
- Cetak tamu mengalokasikan nomor `MAX+1` hanya untuk **Juara 1/2/3 dan Pesilat Terbaik**. Label **Peserta** tetap bisa dicetak, tetapi `no_sertifikat` dibiarkan `NULL`. Nomor yang sudah ada dipakai ulang. Alokasi memakai service yang sama dengan admin (`NomorSertifikat`).
- Halaman publik memakai layout terpisah `layouts/publik.blade.php` (tanpa sidebar admin).

---

## 6.9 Blangko & Desainer Penempatan Teks

**Prinsip utama: satu unit — milimeter.** Koordinat elemen disimpan dalam mm; satuan yang sama berlaku di browser (1 mm = 3,78 px @96 dpi) maupun di DOMPDF, sehingga **yang terlihat di desainer = yang tercetak**.

| Konsep | Penjelasan |
|---|---|
| `blangkos` | Gambar blangko (sertifikat/SK/sertifikat sekolah) **atau** file Blade, orientasi, ukuran kertas (mm), status aktif. Hanya **1 blangko aktif per jenis** (paling baru dipakai). |
| `blangkos.jenis` | `sertifikat` (peserta & official), `sk` (SK juara), atau `sertifikat_sekolah` (ucapan terima kasih sekolah). Disimpan sebagai **VARCHAR**, bukan ENUM, agar jenis baru tidak perlu migrasi enum. |
| `blangkos.mode` | `canvas` (gambar + kotak teks) atau `blade` (file `.blade.php`). |
| `blangkos.view_blade` | Nama view Blade (mis. `blangko.contoh-sertifikat`), hanya diisi untuk mode `blade`. |
| `blangkos.ukuran_kertas` | `a4` (210×297 mm), `f4` (215×330 mm), `letter` (216×279 mm), atau `custom`. `lebar_mm`/`tinggi_mm` dihitung otomatis dari ukuran + orientasi (landscape menukar), dan menjadi ukuran `@page` DOMPDF untuk kedua mode. |
| `blangko_elemen` | Kotak teks di atas gambar: `x_mm`, `y_mm`, `lebar_mm`, `tinggi_mm`, gaya (font, ukuran pt, tebal/miring, rata horizontal & vertikal, warna, z-index). Hanya dipakai mode `canvas`. |
| `teks_varians` | Varian isi teks untuk satu blangko (mis. `prestasi`, `pemasalan`), dipakai kedua mode. |

**Tipe elemen:**

| Tipe | Isi | Contoh |
|---|---|---|
| `data` | Ambil dari data peserta/official/sekolah/Pengaturan | `{nama}`, `{sekolah}`, `{kontingen}`, `{kategori}`, `{kelompok_usia}`, `{juara_romawi}`, `{no_sertifikat}`, `{no_sk}`, `{tanggal}` |
| `teks` | Teks bebas (boleh pakai placeholder `{NAMA}` dll.) | "Diberikan kepada {NAMA} …" |
| `varian` | Isi varian sesuai jenis sertifikat (lihat di bawah) | Varian `pemasalan` untuk Usia Dini 1 |
| `tabel` | Tabel daftar juara (untuk blangko SK) | Baris: Nama, Kontingen, Kategori, Kelas, Juara |

**Pemilihan varian otomatis** (`BlangkoRenderer::varianUntuk()`): kelompok usia `Pra Usia Dini` & `Usia Dini 1` → varian **pemasalan**; sisanya (Usia Dini 2, Pra Remaja, Remaja, Dewasa) → varian **prestasi**. Daftar kelompok usia untuk "pemasalan" dapat diubah di Pengaturan (`varian_pemasalan`).

**Renderer** (`app/Services/BlangkoRenderer.php`): menghasilkan HTML berisi gambar blangko + kotak elemen absolut. HTML yang sama dipakai untuk (a) preview di desainer, (b) preview dengan data contoh, dan (c) PDF (via `Pdf::loadHTML`) — sehingga tidak ada jalur render terpisah yang bisa berbeda.

### 6.9.1 Mode Blade (template HTML/CSS sendiri)

Selain kanvas, blangko dapat memakai file Blade dari **`resources/views/blangko/`** (dan subfolder `blangko/template/`). Daftar file dipindai otomatis oleh `Blangko::pilihanTemplate()`; halaman admin (`index`, `form`, `desainer`, `preview`, `blade`) dikecualikan. Validasi memakai `Rule::in(array_keys(pilihanTemplate()))`, sehingga view dari folder lain (mis. `pdf.*`) **ditolak 422**.

**Kontrak template:**

- Tulis **hanya isi halaman** — satu `<div>` berukuran `{{ $blangko->lebar_mm }}mm × {{ $blangko->tinggi_mm }}mm`.
- Jangan menulis `<html>`, `<head>`, atau `<body>`; sistem membungkus dokumen dan menetapkan `@page { size: <lebar>mm <tinggi>mm; margin: 0 }`.
- Untuk beberapa halaman (bulk / beberapa peserta), sistem menambahkan `page-break-after` di antara halaman.
- **Sertifikat:** satu render = satu peserta. Bulk ditangani `LayananCetak` dengan merender template berulang untuk setiap peserta.
- **SK:** satu render = satu halaman/peserta. Template contoh `contoh-sk-sbcc` memakai `$data`; `$baris` tetap tersedia untuk template SK bertipe tabel (bulk: satu baris).
- Jangan mengandalkan `padding` pada div halaman: DOMPDF tidak selalu menerapkan `box-sizing: border-box`; gunakan posisi absolut atau elemen dalam agar tinggi tidak melebihi ukuran kertas.

**Variabel yang diterima template** (`BlangkoRenderer::renderBlade()`):

| Variabel | Isi |
|---|---|
| `$blangko` | Model `Blangko` (`lebar_mm`, `tinggi_mm`, `orientasi`, `jenis`). |
| `$data` | Data subjek: `nama`, `sekolah` (nama sekolah untuk blangko `sertifikat_sekolah`), `jenis_kelamin` (L/P), `jenis_kelamin_teks` (Putra/Putri), `kontingen`, `kategori`, `kelas`, `kelompok_usia`, `juara`, `juara_romawi`, `label_juara`, `no_sertifikat`, `no_sk`, `jabatan`, `tanggal`, kop (`nama_kejuaraan`, `penyelenggara`, `tempat_tanggal`, `kota_ttd`, `nama_ttd`, `jabatan_ttd`). Untuk sekolah, `no_sertifikat` selalu string kosong. |
| `$kop` | Identitas dokumen dari Pengaturan: `nama_kejuaraan`, `singkatan`, `penyelenggara`, `tempat_tanggal`, `nama_ttd`, `jabatan_ttd`, `kota_ttd`, `logo_kiri`, `logo_kanan`, `background` (path absolut untuk DOMPDF; menjadi `null` bila `$pakaiBackground` false). |
| `$varian` | Kunci varian terpilih (`prestasi` / `pemasalan`). |
| `$varianTeks` | Isi varian yang sudah disubstitusi placeholder (`{NAMA}`, `{KONTINGEN}`, …). |
| `$baris` | Khusus SK: daftar baris `[nama, kontingen, kategori, kelas, juara]`. |
| `$pakaiBackground` | `true`/`false`; `false` menyembunyikan gambar latar template (khusus cetak admin). Tamu selalu `true`. |

**Alur cetak:** `LayananCetak` memilih jalur berdasarkan `$blangko->pakaiBlade()`; mode Blade memanggil `renderBlade()` per subjek lalu `dokumenBlade()` membungkusnya dengan `@page` ukuran blangko. Mode kanvas tetap memakai `BlangkoRenderer::render()` + gambar, dengan `@page` berukuran `lebar_mm × tinggi_mm` yang sama (bukan lagi hardcode A4). Tanpa blangko aktif, keduanya jatuh ke layout bawaan. Untuk sertifikat, admin dapat mengirim `pakai_background=0` (tanpa background) atau `1`/tidak diisi (dengan background); nilai ini diteruskan ke template Blade, gambar kanvas, dan layout PDF bawaan. Endpoint tamu selalu memaksa `true` dan mengabaikan parameter tersebut.

**Ukuran kertas:** form blangko menyediakan pilihan `A4`, `F4/Folio`, `Letter`, dan `Custom` + orientasi. Untuk preset, input lebar/tinggi hanya tampilan (readonly) dan server mengabaikan nilai manual; untuk `custom`, keduanya wajib diisi (50–600 mm).

**Editor Blade** (`resources/views/blangko/blade.blade.php`): memilih file template, menampilkan daftar variabel, path file, isi file (read-only), pengelolaan varian, serta tombol Preview/Pengaturan. Halaman Pengaturan (`blangko/form.blade.php`) dipakai untuk memilih mode `canvas`/`blade` saat membuat atau mengubah blangko.

**Contoh template siap pakai:** `blangko/contoh-sertifikat.blade.php` (A4 landscape), `blangko/contoh-sbcc.blade.php` (satu halaman per peserta; bulk di-loop oleh `LayananCetak`), `blangko/contoh-sk.blade.php` / `blangko/contoh-sk-sbcc.blade.php` (A4 portrait, satu halaman per peserta; template contoh memakai `$data`, `$baris` tersedia untuk template tabel), dan `blangko/contoh-sekolah-sbcc.blade.php` (satu halaman per sekolah, latar `public/blangko-assets/sert-sekolah.jpg`, tanpa nomor).

**Desainer** (`resources/views/blangko/desainer.blade.php` + `public/js/blangko.js`):

- Panel kiri: form elemen (label, tipe, sumber data, isi, posisi/ukuran, gaya) + pengelolaan varian teks.
- Panel kanan: kanvas berisi gambar blangko; setiap elemen adalah kotak yang bisa **diseret** (memindah) dan **diperbesar/diperkecil** lewat sudut kanan-bawah.
- Panel **Daftar Elemen**: setiap elemen punya tombol **Edit** (memuat propertinya ke form) dan **Hapus** (dengan konfirmasi; elemen langsung hilang dari kanvas & daftar). Klik kotak di kanvas = memilih elemen, bukan menyimpan.
- Posisi/ukuran tersimpan otomatis setelah kotak digeser dilepas (POST AJAX `blangko.elemen.simpan` dengan `Accept: application/json`) — **tanpa reload**, sehingga elemen tetap terpilih dan bisa langsung diedit.
- Simpan/edit properti lewat tombol **Simpan Elemen** (AJAX); elemen baru memuat ulang halaman sekali agar kotak & daftar ikut muncul. Hapus via `DELETE` AJAX.
- Konversi piksel→mm di JS: `MM = 96 / 25.4`; gerakan < 2 px dianggap klik (bukan geser).

**Route (khusus admin):** `GET /blangko`, `GET /blangko/create`, `POST /blangko`, `GET /blangko/{blangko}` (desainer **atau** editor Blade, tergantung `mode`), `GET /blangko/{blangko}/pengaturan` (`blangko.form` — form mode/ukuran/gambar), `PUT /blangko/{blangko}`, `DELETE /blangko/{blangko}`, `GET /blangko/{blangko}/preview`, `POST /blangko/{blangko}/elemen`, `DELETE /blangko/{blangko}/elemen/{elemen}`, `POST /blangko/{blangko}/varian`, `DELETE /blangko/{blangko}/varian/{varian}`.

> **Catatan:** saat ada blangko aktif untuk jenis dokumen, PDF **menggunakan blangko** (mode kanvas: gambar + elemen; mode blade: file template). Tanpa blangko, sistem kembali ke layout bawaan (`pdf/sertifikat-peserta`, `pdf/sk-juara`, `pdf/sertifikat-official`).

### 6.10 Sertifikat Sekolah (FR-18)

Fitur khusus **admin** untuk mencetak sertifikat ucapan terima kasih kepada sekolah yang berpartisipasi.

| Aspek | Implementasi |
|---|---|
| Data | Tabel `sekolahs`: `nama` (unique, 150), `terakhir_dicetak_at` (nullable), `jumlah_cetak` (default 0), timestamps. Model `App\Models\Sekolah`. |
| Daftar | `SekolahController@index` menampilkan semua sekolah, pencarian `q`, urut terakhir dicetak lalu nama; status **Sudah/Belum pernah dicetak**, waktu terakhir, dan jumlah cetak. |
| Tambah | `SekolahController@store` hanya memvalidasi `nama` (wajib, unik, maks 150). Tidak ada nomor sertifikat. |
| Cetak | `CetakController@sertifikatSekolah` → `LayananCetak::sertifikatSekolah()`. Memilih `Blangko::aktif('sertifikat_sekolah')`; jika ada, render kanvas/Blade dengan `BlangkoRenderer::dataSekolah()`. Jika tidak ada, fallback `pdf/sertifikat-sekolah` berbasis `public/blangko-assets/sert-sekolah.jpg`. |
| Penomoran | **Tidak ada nomor sertifikat.** `dataSekolah()['no_sertifikat']` selalu `''`; template sekolah tidak menampilkan nomor. |
| Audit | Setelah PDF dibuat, `terakhir_dicetak_at = now()`, `jumlah_cetak += 1`, dan `print_logs` dicatat dengan `jenis_dokumen = 'sertifikat_sekolah'`, `jumlah_data = 1`, `konteks = nama sekolah`. |
| Nama file | PDF memakai nama sekolah, mis. `SMA Negeri 1.pdf` (tanpa ID/timestamp); karakter ilegal untuk nama file dibersihkan, nama kosong jatuh ke `Sekolah.pdf`. |
| Otorisasi | Route `/sekolah` (GET/POST) dan `/cetak/sertifikat/sekolah/{sekolah}` berada di grup middleware `admin`; operator/tamu → 403/redirect. |
| Blangko | Jenis `sertifikat_sekolah` terpisah dari `sertifikat` dan `sk`; form blangko menyediakan pilihan jenis ini. Preview blangko sekolah memakai data contoh `NAMA SEKOLAH`, bukan data peserta. |

**Alur cetak sekolah:**

```text
Admin → /sekolah → tambah nama sekolah → klik Cetak Sertifikat
  → Blangko::aktif('sertifikat_sekolah')
      ├─ ada  → render kanvas/Blade dengan dataSekolah()
      └─ tidak ada → pdf/sertifikat-sekolah (sert-sekolah.jpg)
  → PDF stream (tanpa nomor) → log + counter sekolah
```

### 6.11 Legalisir Sertifikat Peserta (FR-19)

Fitur khusus **admin** untuk mencetak sertifikat peserta versi **hitam putih** dengan **tepat satu stempel legalisir berwarna**. Rincian rencana & status ada di [`LEGALISIR_PLAN.md`](LEGALISIR_PLAN.md).

| Aspek | Implementasi |
|---|---|
| Data stempel | Tabel `stempel_legalisirs`: `nama` (unique, 150), `gambar` (path relatif disk `public`), `x_mm`/`y_mm`/`lebar_mm`/`tinggi_mm` (DECIMAL 6,2), `aktif`, timestamps. Model `App\Models\StempelLegalisir` (cast float/boolean, scope `aktif`, `pathGambar()`, `urlGambar()`, `labelPosisi()`). |
| Aturan posisi | **1 desain = 1 posisi tetap**, koordinat dari sudut kiri atas halaman. Batas divalidasi terhadap ukuran blangko peserta aktif; fallback 297 × 210 mm. |
| Halaman pemilihan | `GET /legalisir?ids[]=...` menampilkan peserta terpilih, hanya 50 pertama, status layak cetak, stempel **aktif**, copy dari setting, total halaman, dan peringatan bila belum ada stempel / melebihi batas. |
| Sumber peserta | Bulk dari checkbox `/peserta` atau tautan per baris; hanya **sertifikat peserta**. Peserta tanpa keterangan juara dilewati; bila semuanya tidak layak → `422`. |
| Blangko | `Blangko::aktif(Blangko::JENIS_SERTIFIKAT)`; mendukung mode `canvas` maupun `blade`. Tanpa blangko aktif → fallback `pdf/sertifikat-peserta` (A4 landscape). |
| Copy | Setting `legalisir_jumlah_copy` (default **2**, validasi 1–20). `LayananCetak::jumlahCopyLegalisir()` meng-clamp nilai. Total halaman = peserta valid × copy. |
| Batas | Maks **50 peserta** per PDF (`ids.max:50`) dan **200 halaman** (`peserta valid × copy`). 50 × 4 = 200 masih boleh; 50 × 5 ditolak. |
| Nomor sertifikat | `NomorSertifikat::assign()` dipanggil sebelum render; aturan lazy lama tidak berubah (Juara 1/2/3 & Pesilat Terbaik; label `Peserta` tetap `NULL`). |
| Overlay stempel | `LayananCetak::dokumenLegalisir()` membungkus setiap halaman dengan `.legalisir-halaman` (ukuran mm blangko) + `.legalisir-grayscale` + `<img class="stempel-legalisir" data-stempel-legalisir="1">` berposisi absolut. Stempel berada **di luar** wrapper grayscale. |
| Hitam putih | `LegalisirRenderer::grayscaleHtml()` mengonversi gambar lokal (`<img src>` dan CSS `url(...)`) ke PNG grayscale memakai **GD**, lalu mengganti path di HTML. Cache di `storage/app/legalisir/` (hash path + mtime); file runtime-only. CSS memaksa `color:#000`, `border-color:#000`, `background-color:transparent` pada isi (agar background blangko tidak tertutup kotak putih), wrapper `.legalisir-grayscale` tetap putih, tanpa shadow. |
| Pengecualian stempel | Tag dengan `data-stempel-legalisir="1"` dilewati saat pemindaian gambar, sehingga stempel tetap berwarna. |
| Audit | `print_logs.jenis_dokumen = 'legalisir'`, `jumlah_data` = jumlah peserta, `jumlah_copy` = copy, `konteks` memuat nama + copy + nama stempel. Riwayat Cetak menampilkan `× N copy` dan total halaman `jumlah_data × jumlah_copy`. |
| Nama file | `legalisir-{slug(nama-stempel)}-{Ymd-His}.pdf`. |
| Otorisasi | Route di grup `auth` + `admin`; operator 403, tamu redirect login. Tidak ada permission baru. |

**Alur cetak legalisir:**

```text
Admin /peserta → centang peserta → /legalisir?ids[]=...
  → pilih 1 stempel aktif + copy (setting)
  → validasi: peserta layak, stempel aktif & gambar ada, posisi dalam halaman, total ≤ 200
  → NomorSertifikat::assign()
  → render isi per peserta (kanvas/Blade/fallback), ulangi sebanyak copy
  → bungkus halaman + overlay stempel (mm)
  → LegalisirRenderer::grayscaleHtml()
  → DOMPDF loadHTML() → stream PDF
  → catat print_logs (legalisir + jumlah_copy)
```

**Catatan implementasi:** `LayananCetak::legalisir()` memeriksa ulang status stempel, keberadaan gambar, dan batas posisi (bisa berubah bila blangko diganti setelah stempel dibuat). `StempelLegalisirRequest` memakai `authorize()` admin dan `withValidator()` untuk memastikan `x_mm + lebar_mm` serta `y_mm + tinggi_mm` tidak melewati halaman.

---

## 7. Konfigurasi DOMPDF penting

```php
// config/dompdf.php
'default_paper_size'   => 'a4',
'default_paper_orientation' => 'portrait',
'default_font'  => 'DejaVu Sans',     // bundel; aman untuk unicode nama Indonesia
'enable_php'    => false,             // jangan enable kecuali butuh
'isRemoteEnabled'=> false,            // pakai path lokal, bukan URL eksternal
```

Catatan anti-jebakan:
- `img src` di PDF → pakai `public_path('storage/x.png')` (bukan `asset()`; DOMPDF `isRemoteEnabled=false`).
- Ukuran font kecil terkadang meleset ±1px di DOMPDF → lakukan uji cetak fisik sebelum hari H.
- Jangan pakai `flex`, `grid`, `position: fixed`; gunakan `float`, `table`, `position:absolute` dalam elemen `position:relative`.
- Batch banyak halaman: gunakan **path file** untuk gambar latar (bukan base64); 50 halaman template Blade ≈ 100 MB puncak memori.

---

## 8. Validasi (FormRequest ringkas)

| Request | Rule utama |
|---|---|
| KontingenRequest | `nama: required|string|max:150|unique:kontingens,nama{,id}`, `kota: nullable|string|max:100` |
| PesertaRequest | `kontingen_id: required|exists:kontingens,id`, `nama: required|string|max:150`, `jenis_kelamin: nullable|in:L,P`, `kategori: required|string|max:120`, `kelas: nullable|string|max:120`, `juara: nullable|string|max:100`, `no_sk: nullable|string|max:100` |
| OfficialRequest | `nama: required|string|max:150`, `kontingen_id: required|exists:kontingens,id`, `jabatan: required|string|max:60` |
| CetakIdsRequest | `ids: required|array|min:1|max:50`, `ids.*: integer|exists:pesertas,id` |
| PengaturanRequest | masing-masing key + upload image rules, termasuk `legalisir_jumlah_copy: nullable|integer|min:1|max:20` |
| StempelLegalisirRequest | `nama: required|string|max:150|unique:stempel_legalisirs,nama{,id}`, `gambar: required(create)/nullable(update)|image|mimes:jpg,jpeg,png,webp|max:4096`, `x_mm`/`y_mm` numeric 0..dimensi halaman, `lebar_mm`/`tinggi_mm` numeric 5..dimensi halaman, `aktif: nullable|boolean`; validasi tambahan `x+lebar` & `y+tinggi` tidak melewati halaman |

---

## 9. Keamanan

| Risiko | Mitigasi |
|---|---|
| Akses tanpa login | Middleware `auth` global (kecuali route login) |
| Brute force login | Rate limiter + delay throttle Laravel |
| CSRF | Token otomatis Blade `@csrf` (middleware default) |
| SQL injection | Eloquent/Query Builder (tanpa raw concatenasi); parameter binding |
| XSS | Blade `{{ }}` auto-escape; jangan `{!! !!}` untuk input user |
| IDOR (cetak data lain) | `whereIn('views id')` tetap butuh validasi role; operator hanya melihat data event ini — OK karena skema single-tenant |
| Mass assignment | `$fillable` eksplisit per model |
| Upload berbahaya | mimes whitelist + max size + hasil simpan di `storage/app/public` (non-executable) |
| Tunnel terbuka ke publik | Private URL + tetap butuh login; session cookie `secure` |

---

## 10. Deployment Lokal → Publik via Cloudflare Tunnel

### 10.1 Menjalankan aplikasi lokal

**Opsi A (direkomendasikan): Laragon / XAMPP**
- Install Laragon, buat project di `C:\laragon\www\sistem-cetak-sertifikat` (atau di `htdocs` XAMPP).
- Apache + MySQL berjalan sebagai service; akses `http://sistem-cetak-sertifikat.test`.
- PHP `extension`: `pdo_mysql`, `gd`, `fileinfo`, `mbstring`, `zip` aktif.

**Opsi B: built-in server**

```bash
php artisan serve --host=127.0.0.1 --port=8000
```
Cukup untuk kejuaraan kecil; catatan server built-in PHP bersifat single-thread → batch PDF besar bisa memblokir request lain sesaat.

### 10.2 Cloudflare Tunnel

**A. Quick Tunnel (tanpa akun, untuk demo/UAT):**

```powershell
tools\cloudflared.exe tunnel --url http://127.0.0.1:8000 --no-autoupdate
# output: https://xxxx-xxxx.trycloudflare.com (URL acak, hilang jika cloudflared berhenti)
# Jika URL baru belum resolve di mesin ini: ipconfig /flushdns
```

**B. Named Tunnel (produksi, perlu domain yang terdaftar di akun Cloudflare):**

```powershell
tools\cloudflared.exe tunnel login
tools\cloudflared.exe tunnel create sertifikat
tools\cloudflared.exe tunnel route dns sertifikat sertifikat.example.com
```

`config.yml`:

```yaml
tunnel: <UUID-tunnel>
credentials-file: C:\Users\Lenovo\.cloudflared\<UUID>.json
ingress:
  - hostname: sertifikat.example.com
    service: http://localhost:8000
    originRequest:
      connectTimeout: 30s
      # cookie / noTLSVerify tidak perlu karena HTTP lokal
  - service: http_status:404
```

Menjalankan + autostart Windows:

```powershell
cloudflared tunnel run sertifikat          # foreground saat demo
cloudflared service install                # daftarkan sebagai Windows Service (produksi)
net start Cloudflared                      # atau restart service
```

**C. Penyetelan Laravel di balik tunnel:**

```env
APP_URL=https://sertifikat.example.com
SESSION_SECURE_COOKIE=true
ASSET_URL=https://sertifikat.example.com  # jika asset URL di-host berbeda (jarang)
```

- Di proyek ini, trusted proxy **tidak** memakai env `TRUSTED_PROXIES`; `bootstrap/app.php` sudah memanggil `$middleware->trustProxies(at: '*')` agar Laravel mempercayai header `X-Forwarded-*` dari cloudflared (redirect URL & secure cookie benar).
- `SESSION_SECURE_COOKIE=true` membuat cookie hanya dikirim via HTTPS. Saat P5 aktif, login lewat `http://localhost:8000` **tidak akan bertahan** — gunakan URL tunnel.
- Rekomendasi tambahan Cloudflare dashboard: WAF allow only URL awalan panitia + **Cloudflare Access** (email OTP) di depan aplikasi untuk lapisan kedua — opsional tapi sangat disarankan karena aplikasi akan jadi public.

**D. Implementasi P5 di repo ini (23 Oktober 2026):**

**Named Tunnel sudah aktif** untuk domain tetap:

| Item | Nilai |
|---|---|
| URL | `https://sbcc.my.id` |
| Tunnel | `sertifikat` (`30c4d639-b76a-44d5-be62-89d4a39384bd`) |
| Config | `tools/cloudflared-named.yml` |
| Credentials | `C:\Users\Lenovo\.cloudflared\30c4d639-b76a-44d5-be62-89d4a39384bd.json` |
| DNS | CNAME `sbcc.my.id` → tunnel (dibuat via `tunnel route dns`) |

Perintah start/stop:

```powershell
# Named Tunnel (produksi, domain tetap)
powershell -ExecutionPolicy Bypass -File tools/p5-start-named.ps1
powershell -ExecutionPolicy Bypass -File tools/p5-stop.ps1

# Quick Tunnel (fallback UAT tanpa domain)
powershell -ExecutionPolicy Bypass -File tools/p5-start.ps1
```

- `p5-start-named.ps1` menjalankan `php artisan serve` dengan env **hanya untuk proses itu**: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `APP_URL=https://sbcc.my.id`, `LOG_LEVEL=warning`; lalu menjalankan `cloudflared tunnel run`.
- URL + PID disimpan di `storage/app/p5-named.json`; log di `storage/logs/cloudflared-named.*.log` dan `storage/logs/serve.*.log`.
- Middleware `App\Http\Middleware\ForceHttps` mengalihkan request HTTP → HTTPS pada mode produksi. Tetap aktifkan **Always Use HTTPS** di dashboard Cloudflare sebagai lapisan utama.
- **Cloudflare Access** (email OTP) untuk area admin belum diaktifkan — disarankan sebelum URL dibagikan luas. Halaman tamu `/cetak` harus tetap publik.
- Checklist UAT & bukti validasi: [`UAT_P5.md`](UAT_P5.md).

### 10.3 Catatan operasional

- **Named Tunnel aktif** di `https://sbcc.my.id`; URL tetap dan cocok untuk hari-H. Quick Tunnel hanya fallback sementara.
- Selama `SESSION_SECURE_COOKIE=true` (mode P5), login lewat `http://localhost:8000` tidak mempertahankan sesi; akses lewat URL HTTPS tunnel. Setelah `tools/p5-stop.ps1`, server lokal kembali memakai `.env` (`APP_ENV=local`, cookie tidak secure).
- Aktifkan **Always Use HTTPS** + **Cloudflare Access** untuk halaman admin di dashboard Cloudflare sebelum event.
- Restart dimulai otomatis jika service Windows: Apache/Laragon + MySQL + Cloudflared.
- Backup rutin: `mysqldump sistem_cetak_sertifikat > backup.sql` (seminggu/jam sebelum event) + folder `storage/app/public`.
- Jaringan panitia di event kadang bervariaasi → tetap tersedia LAN (`http://192.168.x.x`) walau internet mati.

---

## 11. Batasan Teknis yang Diterima

| Batasan | Penjelasan |
|---|---|
| Verifikasi visual PDF harus manual sekali | Template CSS diuji di DOMPDF; posisi nggak 100% pixel-perfect antar browser — bisa diterima karena yang dicetak konkuren dari server, bukan dari browser user. |
| Batch 50 | DOMPDF memori; di atas itu perlu queue/loop chunk — out of scope MVP |
| Legalisir 50 peserta / 200 halaman | PDF legalisir mengulang halaman sebanyak copy; batas 200 halaman menjaga stabilitas DOMPDF. Di atas itu ditolak `422` |
| Copy legalisir 1–20 | Setting dibatasi 1–20; default 2. Total halaman tetap tunduk pada batas 200 |
| Grayscale berbasis GD | Hanya memproses gambar lokal yang bisa dibaca GD (jpg/png/gif/webp); URL eksternal dibiarkan (DOMPDF `isRemoteEnabled=false`). Kualitas hasil mengikuti gambar sumber |
| Nomor sertifikat permanen | Nomor ter-assign saat PDF pertama dibuat (admin atau tamu untuk Juara 1/2/3/Pesilat Terbaik) walau belum di-print fisik; nomor terkunci di data `pesertas` dan dipakai ulang selamanya. Label `Peserta` tidak di-assign. Jika salah input, nomor hanya bisa dikosongkan via edit data (query dirujuk di UI cetak) |
| Single event | Tidak dukungan multi-kejuaraan paralel di satu install |

---

## 12. Pengujian

| No | Kasus Uji | Promise |
|---|---|---|
| UT-01 | Login salah 6× | Terkunci (429) sementara |
| UT-02 | CRUD kontingen + peserta + official lengkap | Data tersimpan, validasi muncul bila kosong |
| UT-03 | Cetak 1 & 3 & 50 peserta | Halaman PDF = jumlah peserta; berurutan sesuai pilihan |
| UT-04 | Cetak SK batch 10 juara `no_sk` homogen | 10 halaman; tiap halaman 1 peserta; nomor di kop sama dengan `no_sk` |
| UT-05 | Batch SK **admin** dengan `no_sk` campur dua nilai / ada yang kosong | 2 peserta = 2 halaman; nomor kosong tetap tercetak kosong |
| UT-05b | Cetak ulang peserta yang sudah punya `no_sertifikat` | Nomor sama persis (tidak generate baru) |
| UT-05c | Cetak baris baru paralel oleh 2 operator | Tidak ada nomor ganda (GET_LOCK + UNIQUE) |
| UT-06 | Akses PDF tanpa login | Redirect ke login |
| UT-07 | Cetak official: nama + dropdown kontingen | Sertifikat tercetak dengan data benar |
| UT-08 | Upload logo/background > 2 MB atau format salah | Ditolak dengan pesan |
| UT-09 | Akses HTTPS via tunnel dari HP luar | Login normal, asset HTTPS, cookie secure |
| UT-10 | Hapus kontingen yang punya peserta | Dialog konfirmasi + cascade hapus peserta |
| UT-11 | Admin menambah sekolah & mencetak sertifikat sekolah | Sekolah tersimpan (nama unik); daftar menampilkan status/counter; PDF tanpa nomor; operator/tamu ditolak; log `sertifikat_sekolah` tercatat |
| UT-12 | Admin mencetak legalisir 2 peserta, copy default 2, lalu copy 3 | PDF hitam putih dengan 1 stempel berwarna; background blangko utuh; halaman = peserta valid × copy (4 lalu 6); log `legalisir` + `jumlah_copy`; stempel nonaktif/gambar hilang/posisi di luar halaman ditolak; operator/tamu ditolak |

Uji otomatis: fitur controller utama (cetak PDF, lazy assignment nomor dengan 2 request paralel, SK satu peserta per halaman dengan `no_sk` opsional, legalisir hitam putih + copy + grayscale) sebagai Laravel Feature Test (PHPUnit/Pest); hasil PDF divalidasi manusia via cetak fisik.

**Status test saat ini (28 Oktober 2026):** **145 test / 535 assertion — semua hijau** setelah P0–P8.

| Test | Cakupan |
|---|---|
| `AuthTest` | Login valid/invalid, lock login setelah 5× gagal, logout, proteksi route |
| `KontingenTest` | CRUD, nama unik, cascade hapus peserta, daftar kontingen |
| `PesertaTest` | CRUD, validasi keterangan juara teks (maks 100), filter kontingen/kategori/kelompok usia/juara, pencarian nama, **dropdown kelompok usia di form edit terpilih walau data lama berbeda huruf besar/kecil**, **cetak satuan & bulk membuka tab baru (`target="_blank"`)** |
| `DashboardTest` | Ringkasan dashboard |
| `CetakSertifikatTest` | PDF satuan & bulk, nomor lazy (**baru = nomor terakhir + 1**, reuse), 1 peserta = 1 halaman, peserta tanpa juara dilewati, **label Peserta tetap dicetak tanpa nomor** (satuan & bulk), Pesilat Terbaik mendapat nomor, ubah juara ke Peserta mengosongkan nomor, **pilihan cetak tanpa background untuk admin**, batas 50, wajib login |
| `CetakSkTest` | PDF SK 1 peserta = 1 halaman (2 peserta → 2 halaman), nomor sama/kosong tetap tercetak, wajib login |
| `OfficialTest` | Simpan official, cetak sertifikat official, **pilihan tanpa background untuk admin**, validasi, hapus, daftar |
| `PengaturanTest` | Update pengaturan, upload logo, operator ditolak (403), form tampil |
| `LogCetakTest` | Riwayat cetak + filter jenis, operator ditolak (403), timezone WIB |
| `UserTest` | Buat akun, email unik, operator ditolak, akun sendiri tak bisa dihapus, update tanpa ganti password |
| `CetakPublikTest` | Halaman tamu tanpa login, paginasi 15/halaman, pencarian nama, cetak sertifikat & SK tamu (termasuk SK tanpa nomor), **cetak tamu Juara 1/2/3 & Pesilat Terbaik mengalokasikan nomor `MAX+1`; label `Peserta` tetap `NULL`**, **tamu selalu memakai background walau meminta `pakai_background=0`**, sertifikat official tamu, rate limit (request ke-31 → 429) |
| `BlangkoTest` | Buat blangko + upload gambar, operator ditolak, simpan elemen (posisi/gaya), **edit elemen** (form & AJAX, tanpa duplikasi), **hapus elemen** (form & AJAX), elemen blangko lain tidak bisa dihapus, simpan varian, pemilihan varian otomatis per kelompok usia, renderer (data + varian + koordinat mm), cetak memakai blangko aktif, preview, **mode Blade** (pemindaian template & pengecualian halaman admin, buat blangko blade, wajib pilih file, tolak view di luar folder, kanvas tidak menyimpan `view_blade`, editor template, preview, render data + varian, **render `contoh-sbcc` dengan/tanpa background**, **background memakai path file (bukan base64)**, **render `contoh-sertifikat` tanpa background kop**, **cetak bulk `contoh-sbcc` 1 halaman/peserta**, **SK `contoh-sk-sbcc` satu halaman per peserta + background path file**, cetak SK 2 peserta = 2 halaman), **ukuran kertas** (dimensi preset A4/F4/Letter + orientasi, input mm manual diabaikan untuk preset, custom wajib isi dimensi, `MediaBox` PDF F4 sesuai mm) |
| `SekolahTest` | Daftar sekolah + status cetak, tambah sekolah nama saja, duplikat ditolak, operator/tamu ditolak, cetak fallback tanpa blangko khusus, counter bertambah, blangko `sertifikat_sekolah` terpisah dari `sertifikat`, `dataSekolah()` tanpa nomor + template sekolah, pembuatan blangko jenis sekolah, filter riwayat `sertifikat_sekolah` |
| `LegalisirTest` | Otorisasi admin/operator/tamu, halaman pilih peserta + stempel + copy default 2, CRUD stempel & validasi batas halaman, cetak 2 copy (2 halaman) dan 2 peserta × 3 copy (6 halaman), memakai blangko peserta aktif, peserta tanpa juara dilewati/ditolak, batas 200 halaman & 50 peserta, stempel nonaktif/gambar hilang/posisi di luar halaman ditolak, simpan setting copy 4 & validasi 0, log `legalisir` + `× 3 copy`, grayscale GD (R=G=B), background blangko tidak tertutup kotak putih (regresi CSS), dan tag stempel tidak diubah |

> **Catatan cetak bulk:** template `contoh-sbcc` dan `contoh-sk-sbcc` memakai **path file absolut** untuk gambar latar, bukan base64. Base64 disalin ke setiap halaman; pada 50 halaman HTML bisa membengkak >300 MB dan memicu `Allowed memory size ... exhausted` di parser DOMPDF. Dengan path file, DOMPDF memuat gambar sekali (50 halaman ≈ 100 MB puncak, 1 gambar).
>
> **Catatan lingkungan test:** PHP di mesin ini **tidak punya `pdo_sqlite`**, jadi `phpunit.xml` diarahkan ke database MySQL khusus `sistem_cetak_sertifikat_test` (`DB_CONNECTION=mysql`, `DB_DATABASE=sistem_cetak_sertifikat_test`). Jalankan `npm run build` **sebelum** `php artisan test` karena layout Blade memakai `@vite`.
>
> **Jumlah halaman PDF dihitung dari isi file** (objek `/Type /Page`), bukan `getCanvas()->get_page_count()` yang selalu mengembalikan 1 pada DomPDF 3.x.

---

## 13. Onboarding Dev (laporan 30 menit pertama)

**Kondisi mesin saat ini (22 Okt 2026):** PHP 8.3.29, Composer 2.4.4, Node 24, MySQL 8.0.30 (Laragon, di `D:\Project\laragon`) — MySQL **tidak** running by default, harus dinyalakan manual.

```powershell
# 1. Nyalakan MySQL (kalau belum jalan)
Start-Process "D:\Project\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe" `
  -ArgumentList "--defaults-file=D:\Project\laragon\bin\mysql\mysql-8.0.30-winx64\my.ini" -WindowStyle Hidden

# 2. Dependency
composer install
npm install

# 3. Environment (sudah ada di repo untuk mesin ini; salin .env.example bila baru)
copy .env.example .env
php artisan key:generate

# 4. Database
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS sistem_cetak_sertifikat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS sistem_cetak_sertifikat_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed

# 5. Aset & symlink
npm run build          # WAJIB sebelum test / sebelum dipakai
php artisan storage:link

# 6. Jalankan
php artisan serve --host=127.0.0.1 --port=8000     # + `npm run dev` bila ingin hot reload

# 7. Test
php artisan test
```

Akun default (seed): `admin@sistem.test / password` (role admin) dan `operator@sistem.test / password` (role operator) — **wajib ganti password saat dipakai sungguhan**. Di mesin event ini password sudah diganti.

---

## 14. Status Implementasi (P0–P4, P6, P7, P8 selesai; P5 sebagian — Named Tunnel aktif, 28 Oktober 2026)

### 14.1 Peta File Awal (P0–P3)

```
app/
├ Http/Controllers/
│  ├ Auth/LoginController.php        login/logout + rate limit 5×/menit
│  ├ DashboardController.php         ringkasan (FR-02)
│  ├ KontingenController.php         CRUD kontingen (FR-03)
│  └ PesertaController.php           CRUD peserta + filter (FR-04, FR-05)
├ Http/Requests/
│  ├ KontingenRequest.php            validasi nama unik, kota opsional
│  └ PesertaRequest.php              validasi kontingen/nama/kategori/juara/no_sk
└ Models/
   ├ User.php                        + role, isAdmin()
   ├ Kontingen.php                   hasMany pesertas & officials
   ├ Peserta.php                     scopeFilter(), labelJuara()
   ├ Official.php                    (data sudah ada, endpoint menyusul P3)
   ├ Setting.php                     getValue()/setValue() key-value
   └ PrintLog.php                    (tabel siap, endpoint menyusul P2/P4)

resources/views/
├ layouts/app.blade.php              shell sidebar + flash + error validasi
├ auth/login.blade.php               halaman login
├ dashboard.blade.php                kartu ringkasan
├ kontingen/{index,form}.blade.php
├ peserta/{index,form}.blade.php    tabel + filter + badge juara + checkbox
└ pdf/                              ← NAMESPACE PDF (P2/P3, belum ada isinya)

resources/js/app.js                 konfirmasi hapus + select all
lang/id/{validation,auth,pagination}.php   pesan berbahasa Indonesia
```

### 14.2 Yang Sudah Terpasang (P0)

- 9 migrasi (users + role, kontingens, pesertas, officials, settings, print_logs, cache, jobs, sessions) → **berhasil** di MySQL 8.0.30.
- Seeder: `AdminSeeder` (admin + operator), `SettingsSeeder` (9 key default dokumen), `DemoSeeder` (3 kontingen, 5 peserta, 3 official).
- `barryvdh/laravel-dompdf` ^3.1 terpasang & terverifikasi render 1 halaman A4 landscape.
- Tailwind 4 + Vite ter-build; font ter-bundle lokal (aman offline).
- `lang/id` untuk pesan validasi Indonesia; `APP_LOCALE=id`.

### 14.3 Yang Sudah Terpasang (P1)

- Login/logout + rate limit (5× gagal → dikunci 60 detik) + regenerasi session.
- Guard `auth` untuk semua route aplikasi; middleware `guest` untuk halaman login.
- CRUD kontingen: nama unik, jumlah peserta, hapus → cascade peserta & official (dengan peringatan di pesan).
- CRUD peserta: kontingen (dropdown), jenis kelamin, kategori (dengan datalist), kelas, juara, `no_sk` manual; `no_sertifikat` tampil read-only ("otomatis saat cetak").
- Daftar peserta: pencarian nama, filter kontingen/kategori/juara, badge juara (nilai lama Emas/Perak/Perunggu; teks bebas seperti Peserta/Pesilat Terbaik ditampilkan apa adanya), checkbox + select-all, paginasi 25, `withQueryString`.

### 14.4 Yang Sudah Terpasang (P2 — Cetak Sertifikat)

- `app/Http/Controllers/CetakController.php` — 3 endpoint PDF (sertifikat peserta, SK juara, sertifikat official) + pencatatan log.
- `app/Services/NomorSertifikat.php` — lazy assignment (MAX+1 + `GET_LOCK` + reuse).
- `resources/views/pdf/layouts/base-pdf.blade.php` — kerangka PDF (namespace terpisah dari Tailwind).
- `resources/views/pdf/sertifikat-peserta.blade.php` — sertifikat A4 landscape, bingkai ganda, kop + logo, nama besar, teks dari Pengaturan, blok TTD, nomor sertifikat; **1 peserta = 1 halaman**.
- `app/Models/Setting.php` — helper `renderTemplate()` (placeholder), `pathGambar()` (untuk DOMPDF), `urlGambar()` (untuk Blade).
- UI: tombol **Cetak** per baris + tombol **Cetak Sertifikat / Cetak SK Juara** bulk (form GET + `ids[]` + `formaction`), plus counter "n peserta dipilih".

### 14.5 Yang Sudah Terpasang (P3 — SK & Official)

- `resources/views/pdf/sk-juara.blade.php` — A4 portrait: kop, judul, nomor SK, Menimbang/Mengingat, tabel juara (No/Nama/Kontingen/Kategori/Kelas/Juara), blok TTD, "Ditetapkan di …".
- **SK:** satu peserta = satu halaman/dokumen; `no_sk` hanya label dan boleh kosong; bulk admin N peserta = N halaman; halaman tamu selalu satu peserta.
- `app/Http/Controllers/OfficialController.php` + `resources/views/official/index.blade.php` — form nama + dropdown kontingen + jabatan, tabel official, tombol cetak per baris.
- `resources/views/pdf/sertifikat-official.blade.php` — sertifikat A4 landscape untuk coach/official.

### 14.6 Yang Sudah Terpasang (P4 — Pengaturan, Log, User)

- `app/Http/Middleware/EnsureAdmin.php` (alias `admin` di `bootstrap/app.php`) → 403 untuk operator.
- `app/Http/Controllers/PengaturanController.php` + view `pengaturan/index` — identitas kejuaraan, penandatangan, upload logo kiri/kanan + background (maks 2 MB, jpg/png/webp), template teks dengan placeholder.
- `app/Http/Controllers/LogCetakController.php` + view `log-cetak/index` — riwayat cetak + filter jenis/tanggal + total halaman.
- `app/Http/Controllers/UserController.php` + views `user/{index,form}` — manajemen akun, akun sendiri tidak bisa dihapus.
- Dashboard: kartu "halaman tercetak hari ini" & total halaman.

### 14.7 Yang Sudah Terpasang (P6 — Cetak Guest & Blangko)

- `app/Http/Controllers/CetakPublikController.php` — halaman tamu (daftar peserta 15/halaman, pencarian, cetak sertifikat/SK, formulir official).
- `app/Http/Controllers/BlangkoController.php` — CRUD blangko (dua mode), elemen, varian, pengaturan, preview.
- `app/Services/BlangkoRenderer.php` — render gambar + elemen (satuan mm), **render Blade** (`renderBlade()`, `teksVarian()`, `kop()`) & pemilihan varian otomatis.
- `app/Services/LayananCetak.php` — **service cetak bersama** untuk admin & tamu (sertifikat peserta, SK, sertifikat official; memakai blangko kanvas **atau** Blade bila ada).
- `resources/views/cetak-publik/{index,official}.blade.php` + `resources/views/layouts/publik.blade.php` — tampilan tamu.
- `resources/views/blangko/{index,form,desainer,blade,preview}.blade.php` + `public/js/blangko.js` — desainer drag & drop + editor template Blade.
- `resources/views/blangko/{contoh-sertifikat,contoh-sk}.blade.php` — template Blade contoh (landscape & portrait).
- `app/Providers/AppServiceProvider.php` — RateLimiter `publik` (60/menit) & `cetak-publik` (30/jam).
- Tabel baru: `blangkos`, `blangko_elemen`, `teks_varians`; kolom baru `pesertas.kelompok_usia`, `blangkos.mode`, `blangkos.view_blade`; `print_logs.user_id` jadi nullable.

### 14.8 Yang Sudah Terpasang (P7 — Sertifikat Sekolah)

- `database/migrations/2026_10_27_000001_create_sekolahs_table.php` — tabel `sekolahs` (`nama` unique, `terakhir_dicetak_at`, `jumlah_cetak`).
- `database/migrations/2026_10_27_000002_add_sertifikat_sekolah_types.php` — `blangkos.jenis` & `print_logs.jenis_dokumen` dari ENUM ke VARCHAR; mendukung `sertifikat_sekolah`.
- `app/Models/Sekolah.php` — model + cast `terakhir_dicetak_at` & `jumlah_cetak`.
- `app/Http/Controllers/SekolahController.php` — daftar, pencarian, tambah sekolah (admin).
- `app/Http/Controllers/CetakController.php` — `sertifikatSekolah()` (admin).
- `app/Services/LayananCetak.php` — `sertifikatSekolah()`: pilih blangko `sertifikat_sekolah`, fallback `pdf/sertifikat-sekolah`, update counter + log.
- `app/Services/BlangkoRenderer.php` — `dataSekolah()`; `no_sertifikat` selalu kosong.
- `app/Models/Blangko.php` — konstanta `JENIS_SEKOLAH`, `pilihanJenis()`, `labelJenis()`.
- `app/Models/BlangkoElemen.php` — field data `sekolah`.
- `resources/views/sekolah/index.blade.php` — daftar + form tambah + tombol cetak.
- `resources/views/blangko/contoh-sekolah-sbcc.blade.php` — template Blade sekolah (latar `sert-sekolah.jpg`).
- `resources/views/pdf/sertifikat-sekolah.blade.php` — fallback PDF sekolah.
- Sidebar **Sertifikat Sekolah**, filter Riwayat Cetak `sertifikat_sekolah`, dan `SekolahTest` (11 test).

### 14.9 Yang Sudah Terpasang (P8 — Legalisir)

- `database/migrations/2026_10_28_000001_create_stempel_legalisirs_table.php` — tabel desain stempel (nama unik, gambar, posisi/ukuran mm, aktif).
- `database/migrations/2026_10_28_000002_add_jumlah_copy_to_print_logs_table.php` — kolom `print_logs.jumlah_copy` (default 1).
- `app/Models/StempelLegalisir.php` — cast float/boolean, scope `aktif`, `pathGambar()`, `urlGambar()`, `labelPosisi()`.
- `app/Http/Requests/StempelLegalisirRequest.php` — validasi CRUD stempel + batas halaman aktif.
- `app/Http/Controllers/LegalisirController.php` — halaman pilih peserta/stempel/copy, endpoint cetak, CRUD stempel.
- `app/Services/LayananCetak.php` — `legalisir()`, `jumlahCopyLegalisir()`, `dokumenLegalisir()`, `stempelHtml()`, `dataStempel()`, `mm()`, logging `jumlah_copy`.
- `app/Services/LegalisirRenderer.php` — grayscale GD untuk `<img>` & CSS `url(...)`, pengecualian `data-stempel-legalisir`, cache `storage/app/legalisir`.
- `resources/views/legalisir/index.blade.php` — pilih peserta, stempel, copy, total halaman.
- `resources/views/legalisir/stempel/{index,form}.blade.php` — daftar & editor stempel + preview posisi.
- `resources/views/pdf/sertifikat-peserta.blade.php` — dukungan opsional `legalisir` + overlay stempel.
- `routes/web.php` — `/legalisir`, `/legalisir/cetak`, `/legalisir/stempel*` (grup `admin`).
- `resources/views/layouts/app.blade.php` — menu **Legalisir**; `resources/views/peserta/index.blade.php` — tombol bulk & per baris.
- `resources/views/pengaturan/index.blade.php` + `PengaturanController` + `PengaturanRequest` — setting `legalisir_jumlah_copy`.
- `resources/views/log-cetak/index.blade.php` + `LogCetakController` — filter `legalisir`, tampilan copy, total halaman.
- `database/seeders/SettingsSeeder.php` — default `legalisir_jumlah_copy = 2`.
- `tests/Feature/LegalisirTest.php` — 22 test / 74 assertion.

### 14.10 Utang Technical untuk Fase Berikutnya

| Utang | tackled di |
|---|---|
| `cloudflared` + named tunnel + hardening `.env` produksi (`TRUSTED_PROXIES`, `SESSION_SECURE_COOKIE`) | P5 |
| UAT bersama panitia + **uji cetak fisik** (ukuran, posisi nama, blanko pre-printed) | P5 |
| Checklist keamanan TRD §9 untuk area yang tersentuh | P5 |
