# Rencana Fitur Legalisir Sertifikat Peserta

| Field | Nilai |
|---|---|
| Proyek | Sistem Cetak Sertifikat (SCS) |
| Fitur | Legalisir sertifikat peserta (hitam putih + stempel) |
| Status | ✅ **Terimplementasi** — 22 test legalisir hijau |
| Tanggal | 28 Oktober 2026 |
| Dokumen terkait | [`PRD.md`](PRD.md), [`TRD.md`](TRD.md), [`DB_SCHEMA.md`](DB_SCHEMA.md), [`UAT_P5.md`](UAT_P5.md) |

---

## 1. Tujuan & Ruang Lingkup

Fitur legalisir memungkinkan **admin** mencetak ulang sertifikat peserta dalam versi **hitam putih** dan memberi **satu stempel legalisir** pada setiap lembar. Fitur ini dipakai untuk kebutuhan arsip/legalisasi dokumen, bukan untuk mencetak sertifikat berwarna biasa.

### In-scope

- Hanya **sertifikat peserta** (bukan SK juara, sertifikat official, atau sertifikat sekolah).
- Hanya **admin**; operator dan tamu tidak boleh mengakses halaman maupun endpoint cetak.
- Memakai **blangko peserta aktif** (`Blangko::JENIS_SERTIFIKAT`). Bila belum ada blangko aktif, memakai layout bawaan `pdf/sertifikat-peserta`.
- Satu sertifikat memakai **tepat satu stempel legalisir**.
- Admin dapat membuat **banyak desain stempel**; setiap desain menyimpan **satu posisi tetap**.
- Jumlah copy per sertifikat dapat diatur di Pengaturan; **default 2**, rentang **1–20**.
- Cetak satuan maupun bulk.
- Hasil PDF hitam putih; warna stempel tetap dipertahankan.
- Nomor sertifikat tetap memakai aturan lama: lazy assignment `MAX+1`, nomor yang sudah ada dipakai ulang, label `Peserta` tetap tanpa nomor.

### Out-of-scope

- Legalisir SK juara, sertifikat official, atau sertifikat sekolah.
- Dua stempel atau lebih dalam satu sertifikat.
- Posisi stempel berbeda per peserta.
- Tanda tangan digital/e-signature.
- Mengubah warna stempel menjadi hitam putih.
- Cetak legalisir oleh operator/tamu.
- Antrean/queue PDF; cetak tetap sinkron.

---

## 2. Alur Pengguna

```mermaid
flowchart TD
    A[Login Admin] --> B[Peserta & Juara]
    B --> C[Centang peserta]
    C --> D[Klik Legalisir]
    D --> E[Halaman /legalisir?ids[]=...]
    E --> F[Pilih satu stempel aktif]
    F --> G[Lihat jumlah copy & total halaman]
    G --> H{Valid?}
    H -->|Tidak| I[Pesan: tidak ada stempel / melebihi batas / peserta tanpa juara]
    H -->|Ya| J[Klik Cetak Legalisir]
    J --> K[PDF hitam putih + stempel, dibuka tab baru]
    K --> L[Log cetak jenis legalisir + jumlah_copy]
```

### 2.1 Dua titik masuk

1. **Bulk:** tombol **Legalisir** pada form aksi di `/peserta` mengirim semua peserta tercentang ke `/legalisir?ids[]=...`.
2. **Satuan:** tautan **Legalisir** pada baris peserta mengirim satu ID ke halaman yang sama.

Halaman `/legalisir` menampilkan peserta terpilih, status kelayakan, pilihan stempel aktif, jumlah copy, total halaman, dan tombol cetak. Tombol cetak membuka PDF di tab baru.

### 2.2 Aturan peserta

- Peserta yang **belum punya keterangan juara** dilewati dengan flash warning.
- Bila **semua** peserta terpilih tidak punya keterangan juara, cetak ditolak `422`.
- Peserta dengan label `Peserta` tetap boleh dicetak dan tetap **tanpa nomor sertifikat**.
- Nomor sertifikat untuk Juara 1/2/3 dan Pesilat Terbaik di-assign saat cetak legalisir bila belum ada.

---

## 3. Skema Data

### 3.1 Tabel `stempel_legalisirs`

```sql
CREATE TABLE `stempel_legalisirs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama`       VARCHAR(150)    NOT NULL,
  `gambar`     VARCHAR(255)    NOT NULL, -- path relatif storage/app/public
  `x_mm`       DECIMAL(6,2)    NOT NULL DEFAULT 237.00,
  `y_mm`       DECIMAL(6,2)    NOT NULL DEFAULT 20.00,
  `lebar_mm`   DECIMAL(6,2)    NOT NULL DEFAULT 40.00,
  `tinggi_mm`  DECIMAL(6,2)    NOT NULL DEFAULT 40.00,
  `aktif`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP       NULL DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stempel_legalisirs_nama_unique` (`nama`),
  KEY `stempel_legalisirs_aktif_index` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Keputusan kunci:**

- **1 desain = 1 posisi.** Tidak ada posisi per peserta atau per cetak.
- Koordinat dan ukuran memakai **milimeter desimal**, sama dengan blangko.
- `x_mm`/`y_mm` dihitung dari **sudut kiri atas** halaman.
- `lebar_mm`/`tinggi_mm` adalah ukuran gambar stempel di halaman.
- Hanya stempel `aktif` yang bisa dipilih saat cetak.
- Gambar disimpan di disk `public` dengan folder `stempel-legalisir/`; disarankan PNG transparan.

### 3.2 Kolom `print_logs.jumlah_copy`

```sql
ALTER TABLE `print_logs`
  ADD COLUMN `jumlah_copy` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `jumlah_data`;
```

- `jumlah_data` = jumlah sertifikat/peserta.
- `jumlah_copy` = copy per sertifikat.
- Total halaman = `jumlah_data × jumlah_copy`.
- Log lama otomatis bernilai 1.
- Riwayat Cetak menampilkan `× N copy` untuk log legalisir (atau log lain dengan copy > 1).
- Total halaman di Riwayat Cetak memakai `SUM(jumlah_data * jumlah_copy)`.

### 3.3 Setting `legalisir_jumlah_copy`

| Key | Default | Validasi | Dipakai |
|---|---|---|---|
| `legalisir_jumlah_copy` | `2` | integer 1–20 | `LayananCetak::jumlahCopyLegalisir()` dan halaman Pengaturan |

`Setting::getValue('legalisir_jumlah_copy', 2)` memberi fallback 2 bila key belum ada. `SettingsSeeder` menambahkan nilai default `2`, tetapi **tidak perlu dijalankan ulang** pada database produksi; key akan dibuat saat admin menyimpan Pengaturan.

---

## 4. Batas Cetak

| Batas | Nilai | Perilaku |
|---|---:|---|
| Peserta per PDF | **50** | Validasi `ids.max:50`; halaman pemilihan hanya menampilkan 50 pertama |
| Halaman per PDF | **200** | `jumlah peserta valid × jumlah copy > 200` → `422` |
| Copy per sertifikat | **1–20** | Validasi Pengaturan; helper meng-clamp ke 1–20 |
| Ukuran gambar stempel | **4 MB** | Validasi upload |

Dengan default 2 copy, 50 peserta menghasilkan 100 halaman. Kombinasi 50 peserta × 4 copy = 200 halaman masih diizinkan; 50 × 5 = 250 halaman ditolak.

---

## 5. Pendekatan Hitam Putih (Grayscale)

### 5.1 Kendala

- DOMPDF **tidak mendukung** CSS `filter: grayscale()`.
- Server tidak menyediakan **Imagick**, **Ghostscript**, atau **pdftoppm**.
- Solusi harus tetap berjalan di Windows/Laragon dengan ekstensi **GD** yang sudah ada.

### 5.2 Solusi

`App\Services\LegalisirRenderer` melakukan dua hal:

1. **Konversi gambar lokal** menjadi file PNG grayscale memakai GD.
   - Memindai tag `<img src="...">`.
   - Memindai CSS `url(...)`.
   - Mendukung path absolut, path `public/`, path `base_path`, `file://`, dan data URI base64.
   - Cache di `storage/app/legalisir/` dengan nama hash; file ini **runtime-only** dan tidak masuk Git.
2. **CSS paksa hitam putih** untuk teks, border, dan background:
   - `color:#000 !important`
   - `border-color:#000 !important`
   - `background-color:transparent !important` pada isi, agar kotak teks tidak menutup background blangko
   - wrapper `.legalisir-grayscale` diberi `background-color:#fff !important`
   - `text-shadow:none`, `box-shadow:none`

### 5.3 Pengecualian stempel

Tag stempel diberi atribut `data-stempel-legalisir="1"`. Renderer **melewati** tag tersebut, sehingga:

- Sertifikat/blangko/logo menjadi hitam putih.
- Stempel tetap berwarna sesuai unggahan admin.
- Stempel diberi **opacity 85%** agar menyatu dengan cetakan hitam putih tanpa menutupi teks di bawahnya.
- Stempel ditempatkan **di luar** wrapper `.legalisir-grayscale` agar tidak terkena aturan CSS grayscale.

### 5.4 Alur render

```text
Peserta + stempel + copy
  → pilih blangko aktif (atau fallback)
  → render isi sertifikat per peserta
  → ulangi isi sebanyak copy
  → bungkus tiap halaman + stempel absolut (mm)
  → LegalisirRenderer::grayscaleHtml()
  → DOMPDF loadHTML()
  → stream PDF
  → catat print_logs
```

- Mode kanvas dan Blade sama-sama memakai `LayananCetak::dokumenLegalisir()`.
- Fallback `pdf/sertifikat-peserta` memakai variabel `legalisir` dan `stempel` opsional.
- Ukuran halaman mengikuti `lebar_mm × tinggi_mm` blangko; fallback A4 landscape.

---

## 6. Validasi Stempel

`App\Http\Requests\StempelLegalisirRequest`:

| Field | Aturan |
|---|---|
| `nama` | wajib, string, maks 150, unik |
| `gambar` | wajib saat create, opsional saat update, image, jpg/jpeg/png/webp, maks 4 MB |
| `x_mm` | wajib, numeric, 0..lebar halaman |
| `y_mm` | wajib, numeric, 0..tinggi halaman |
| `lebar_mm` | wajib, numeric, minimal 5, maks lebar halaman |
| `tinggi_mm` | wajib, numeric, minimal 5, maks tinggi halaman |
| `aktif` | opsional boolean |

Validasi tambahan (`withValidator`):

- `x_mm + lebar_mm` tidak boleh melebihi lebar halaman aktif.
- `y_mm + tinggi_mm` tidak boleh melebihi tinggi halaman aktif.

Batas halaman diambil dari **blangko peserta aktif**; bila tidak ada blangko, fallback 297 × 210 mm.

Saat cetak, `LayananCetak::legalisir()` memeriksa ulang batas posisi untuk berjaga-jaga bila blangko berubah setelah stempel dibuat.

---

## 7. Routing & Komponen

Semua route berada di grup middleware `auth` + `admin`.

| Method | URI | Nama Route | Controller@method |
|---|---|---|---|
| GET | `/legalisir` | `legalisir.index` | `LegalisirController@index` |
| GET | `/legalisir/cetak` | `legalisir.cetak` | `LegalisirController@cetak` |
| GET | `/legalisir/stempel` | `legalisir.stempel.index` | `LegalisirController@stempelIndex` |
| GET | `/legalisir/stempel/create` | `legalisir.stempel.create` | `LegalisirController@stempelCreate` |
| POST | `/legalisir/stempel` | `legalisir.stempel.store` | `LegalisirController@stempelStore` |
| GET | `/legalisir/stempel/{stempel}/edit` | `legalisir.stempel.edit` | `LegalisirController@stempelEdit` |
| PUT | `/legalisir/stempel/{stempel}` | `legalisir.stempel.update` | `LegalisirController@stempelUpdate` |
| DELETE | `/legalisir/stempel/{stempel}` | `legalisir.stempel.destroy` | `LegalisirController@stempelDestroy` |

### 7.1 File utama

| File | Fungsi |
|---|---|
| `app/Models/StempelLegalisir.php` | Model, cast float/boolean, scope `aktif`, path/URL gambar, label posisi |
| `app/Http/Requests/StempelLegalisirRequest.php` | Validasi CRUD stempel + batas halaman |
| `app/Http/Controllers/LegalisirController.php` | Halaman pilih peserta, endpoint cetak, CRUD stempel |
| `app/Services/LayananCetak.php` | `legalisir()`, copy count, overlay stempel, log, nama file |
| `app/Services/LegalisirRenderer.php` | Grayscale GD + penggantian gambar HTML |
| `resources/views/legalisir/index.blade.php` | UI pilih peserta/stempel/copy |
| `resources/views/legalisir/stempel/{index,form}.blade.php` | Daftar & editor stempel + preview posisi |
| `resources/views/pdf/sertifikat-peserta.blade.php` | Dukungan opsional grayscale + stempel |
| `database/migrations/2026_10_28_000001_create_stempel_legalisirs_table.php` | Tabel stempel |
| `database/migrations/2026_10_28_000002_add_jumlah_copy_to_print_logs_table.php` | Kolom copy |

### 7.2 Nama file PDF

```text
legalisir-{slug-nama-stempel}-{Ymd-His}.pdf
```

Contoh: `legalisir-stempel-panitia-20261028-143501.pdf`.

---

## 8. Integrasi yang Dipertahankan

- **Nomor sertifikat:** `NomorSertifikat::assign()` dipanggil sebelum render; aturan lama tidak berubah.
- **Blangko peserta:** `Blangko::aktif(Blangko::JENIS_SERTIFIKAT)`; varian teks otomatis tetap memakai `BlangkoRenderer::varianUntuk()`.
- **Fallback:** `pdf/sertifikat-peserta` bila tidak ada blangko aktif.
- **Background:** legalisir memakai background blangko, lalu diubah menjadi grayscale.
- **Log:** jenis dokumen baru `legalisir`; `print_logs.jenis_dokumen` sudah VARCHAR(40), jadi tidak perlu migrasi enum.
- **Riwayat Cetak:** filter jenis `legalisir`, tampilan copy, dan total halaman.
- **Pengaturan:** key `legalisir_jumlah_copy` di kartu Legalisir.
- **Sidebar:** menu **Legalisir** untuk admin.
- **Halaman peserta:** tombol bulk & tautan per baris hanya untuk admin.

---

## 9. Rencana Pengujian

### 9.1 Test otomatis

`tests/Feature/LegalisirTest.php` — **22 test / 74 assertion**:

| Kelompok | Cakupan |
|---|---|
| Otorisasi | operator 403, tamu redirect login |
| Halaman | peserta & stempel tampil, copy default 2, peringatan bila belum ada stempel |
| CRUD stempel | create, update, delete, validasi posisi melewati halaman |
| Cetak | default 2 copy, setting 3 copy, 2 peserta × 3 copy = 6 halaman |
| Blangko | memakai blangko peserta aktif |
| Kelayakan | semua peserta tanpa juara ditolak; sebagian dilewati |
| Batas | 200 halaman, 50 peserta |
| Stempel | nonaktif ditolak, gambar hilang ditolak, posisi di luar halaman ditolak |
| Pengaturan | simpan copy 4, validasi 0 ditolak |
| Log | jenis legalisir + `× 3 copy` tampil |
| Grayscale | gambar berwarna menjadi R=G=B; tag stempel tidak diubah; background blangko tidak tertutup kotak putih |

### 9.2 Suite penuh

- **145/145 test hijau**, **535 assertion**.
- `vendor/bin/pint --dirty` lolos.
- `npm run build` lolos.

### 9.3 Uji manual yang disarankan

1. Buka `/peserta`, centang 2 peserta, klik **Legalisir**.
2. Pastikan halaman menampilkan 2 peserta, copy 2, total 4 halaman.
3. Pilih stempel, cetak; cek PDF 4 halaman, hitam putih, stempel berwarna di posisi yang sama.
4. Ubah Pengaturan copy menjadi 3; ulangi; cek 6 halaman dan log `× 3 copy`.
5. Buat stempel dengan posisi melewati halaman; pastikan form menolak.
6. Nonaktifkan stempel; pastikan tidak muncul di pilihan dan endpoint menolak.
7. Cek Riwayat Cetak: jenis **Legalisir**, jumlah data, copy, total halaman.
8. Uji cetak fisik: pastikan stempel tidak terpotong dan posisi sesuai desain.

---

## 10. Deployment & Operasional

```bash
# 1. Migrasi aditif (wajib)
php artisan migrate

# 2. Build aset (bila view berubah)
npm run build

# 3. Bersihkan cache view/config bila perlu
php artisan optimize:clear
```

- Tidak perlu menjalankan `SettingsSeeder` pada database produksi; default copy 2 sudah aktif lewat fallback.
- `storage/app/public/stempel-legalisir/` harus dapat ditulis; `php artisan storage:link` sudah diperlukan aplikasi.
- Cache grayscale `storage/app/legalisir/` boleh dihapus kapan saja; akan dibuat ulang saat cetak.
- Tidak ada perubahan pada file PDF lama, SK, official, sekolah, atau nama file sertifikat biasa.

---

## 11. Keputusan & Catatan

| # | Keputusan | Alasan |
|---|---|---|
| L1 | Legalisir hanya untuk sertifikat peserta | SK/official/sekolah punya alur dan format berbeda |
| L2 | 1 desain stempel = 1 posisi | Menghindari editor posisi per cetak; konsisten dengan blangko |
| L3 | 1 sertifikat = 1 stempel | Kebutuhan legalisasi sederhana; mencegah tumpang tindih |
| L4 | Copy default 2, maks 20 | Umumnya arsip + panitia; tetap bisa 3/4 dst. |
| L5 | Batas 50 peserta / 200 halaman | Menjaga stabilitas DOMPDF di server lokal |
| L6 | Grayscale via GD + CSS | Imagick/Ghostscript tidak tersedia; DOMPDF tanpa `filter` |
| L7 | Stempel tidak di-grayscale | Stempel legalisir perlu tetap terlihat sebagai stempel berwarna |
| L8 | Stempel disimpan di disk `public` | Sama dengan aset gambar lain; DOMPDF membaca path lokal |
| L9 | Log `legalisir` + `jumlah_copy` | Audit jumlah lembar dan copy |
| L10 | Tidak ada izin baru | Cukup middleware `admin` yang sudah ada |

---

## 12. Status Implementasi

| Komponen | Status |
|---|---|
| Migrasi `stempel_legalisirs` | ✅ Terpasang & sudah dijalankan di DB aplikasi |
| Migrasi `print_logs.jumlah_copy` | ✅ Terpasang & sudah dijalankan di DB aplikasi |
| Model, request, controller, service | ✅ |
| Halaman pilih legalisir & CRUD stempel | ✅ |
| Grayscale GD + pengecualian stempel | ✅ |
| Pengaturan copy & Riwayat Cetak | ✅ |
| Test otomatis | ✅ 22 test / 74 assertion |
| Suite penuh | ✅ 145 test / 535 assertion |
| UAT cetak fisik | ⏳ Menunggu printer & kertas blanko asli |
