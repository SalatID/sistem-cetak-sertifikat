<?php

namespace App\Services;

use App\Models\Blangko;
use App\Models\BlangkoElemen;
use App\Models\Official;
use App\Models\Peserta;
use App\Models\Sekolah;
use App\Models\Setting;
use App\Models\TeksVarian;

/**
 * Render blangko (gambar) + elemen teks ke HTML.
 *
 * Semua koordinat memakai **milimeter** — satuan yang sama berlaku di browser
 * (1 mm = 3,78 px @96dpi) maupun di DOMPDF, sehingga preview di editor
 * identik dengan hasil cetak PDF.
 */
class BlangkoRenderer
{
    public const VARIAN_PRESTASI = 'prestasi';

    public const VARIAN_PEMASALAN = 'pemasalan';

    /**
     * Tentukan varian teks otomatis dari kelompok usia peserta.
     *
     * Daftar kelompok usia yang memakai sertifikat *pemasalan* diambil dari
     * Pengaturan (`varian_pemasalan`, default: "Pra Usia Dini, Usia Dini 1").
     */
    public static function varianUntuk(?string $kelompokUsia): string
    {
        $daftar = array_filter(array_map('trim', explode(',', (string) Setting::getValue(
            'varian_pemasalan',
            'Pra Usia Dini, Usia Dini 1',
        ))));

        if ($kelompokUsia !== null && in_array($kelompokUsia, $daftar, true)) {
            return self::VARIAN_PEMASALAN;
        }

        return self::VARIAN_PRESTASI;
    }

    /**
     * Konteks data untuk elemen bertipe 'data'.
     *
     * @return array<string, string>
     */
    public static function dataPeserta(Peserta $peserta): array
    {
        return array_merge(self::dataUmum(), [
            'nama' => $peserta->nama,
            'jenis_kelamin' => (string) $peserta->jenis_kelamin,
            'jenis_kelamin_teks' => match ($peserta->jenis_kelamin) {
                'L' => 'Putra',
                'P' => 'Putri',
                default => '',
            },
            'kontingen' => (string) $peserta->kontingen?->nama,
            'kategori' => $peserta->kategori,
            'kelas' => (string) $peserta->kelas,
            'kelompok_usia' => (string) $peserta->kelompok_usia,
            'juara' => $peserta->juara !== null ? (string) $peserta->juara : '-',
            'juara_romawi' => $peserta->juara !== null ? $peserta->juaraRomawi() : '-',
            'label_juara' => $peserta->labelJuara(),
            'no_sertifikat' => $peserta->no_sertifikat !== null
                ? str_pad((string) $peserta->no_sertifikat, 3, '0', STR_PAD_LEFT)
                : '-',
            'no_sk' => (string) $peserta->no_sk,
            'jabatan' => '-',
        ]);
    }

    /**
     * Konteks data untuk sertifikat coach/official.
     *
     * @return array<string, string>
     */
    public static function dataOfficial(Official $official): array
    {
        return array_merge(self::dataUmum(), [
            'nama' => $official->nama,
            'kontingen' => (string) $official->kontingen?->nama,
            'kategori' => '-',
            'kelas' => '',
            'kelompok_usia' => '',
            'juara' => '-',
            'juara_romawi' => '-',
            'label_juara' => '-',
            'no_sertifikat' => '-',
            'no_sk' => '-',
            'jabatan' => $official->jabatan,
        ]);
    }

    /**
     * Konteks data untuk sertifikat sekolah (ucapan terima kasih).
     *
     * Sertifikat sekolah tidak memakai nomor sertifikat. Field `nama` tetap
     * diisi agar template/element lama yang memakai {NAMA} dapat digunakan.
     *
     * @return array<string, string>
     */
    public static function dataSekolah(Sekolah $sekolah): array
    {
        return array_merge(self::dataUmum(), [
            'nama' => $sekolah->nama,
            'sekolah' => $sekolah->nama,
            'kontingen' => '',
            'kategori' => '',
            'kelas' => '',
            'kelompok_usia' => '',
            'juara' => '-',
            'juara_romawi' => '-',
            'label_juara' => '-',
            'no_sertifikat' => '',
            'no_sk' => '',
            'jabatan' => '',
        ]);
    }

    /**
     * Data kop dokumen yang berasal dari Pengaturan.
     *
     * @return array<string, string>
     */
    public static function dataUmum(): array
    {
        return [
            'tanggal' => now()->translatedFormat('d F Y'),
            'jenis_kelamin' => '',
            'jenis_kelamin_teks' => '',
            'nama_kejuaraan' => (string) Setting::getValue('nama_kejuaraan', ''),
            'penyelenggara' => (string) Setting::getValue('penyelenggara', ''),
            'tempat_tanggal' => (string) Setting::getValue('tempat_tanggal', ''),
            'kota_ttd' => (string) Setting::getValue('kota_ttd', ''),
            'nama_ttd' => (string) Setting::getValue('nama_ttd', ''),
            'jabatan_ttd' => (string) Setting::getValue('jabatan_ttd', ''),
        ];
    }

    /**
     * Render seluruh elemen blangko menjadi HTML.
     *
     * @param  array<string, mixed>  $data
     * @param  string|null  $gambarUrl  URL (web) atau path filesystem (PDF)
     * @param  array<int, array<string, mixed>>  $baris  data untuk elemen bertipe 'tabel'
     */
    public static function render(
        Blangko $blangko,
        array $data,
        ?string $gambarUrl = null,
        ?string $varianKey = null,
        array $baris = [],
    ): string {
        $html = sprintf(
            '<div class="blangko" style="position:relative; width:%dmm; height:%dmm; overflow:hidden; background-color:#ffffff;">',
            $blangko->lebar_mm,
            $blangko->tinggi_mm,
        );

        if ($gambarUrl !== null) {
            $html .= sprintf(
                '<img src="%s" style="position:absolute; left:0; top:0; width:%dmm; height:%dmm;">',
                e($gambarUrl),
                $blangko->lebar_mm,
                $blangko->tinggi_mm,
            );
        }

        foreach ($blangko->elemen as $elemen) {
            $html .= self::renderElemen($elemen, $data, $varianKey, $baris);
        }

        return $html.'</div>';
    }

    /**
     * Render blangko berbasis file Blade (mode "blade").
     *
     * Template menerima variabel:
     * - `$blangko`     : model Blangko (ukuran/orientasi)
     * - `$data`        : konteks data (nama, kontingen, kategori, juara, nomor, …)
     * - `$kop`         : identitas dokumen (nama kejuaraan, penyelenggara, logo, penandatangan)
     * - `$baris`       : baris tabel (khusus SK)
     * - `$varian`      : kunci varian ('prestasi' / 'pemasalan')
     * - `$varianTeks`  : isi varian yang sudah disubstitusi placeholder
     * - `$pakaiBackground` : false untuk cetak tanpa gambar latar (khusus admin)
     *
     * Kontrak: template hanya menulis **isi halaman** (satu `<div>` berukuran
     * mm); jangan menulis `<html>`/`<body>` — pembungkus & `@page` dibuat sistem.
     *
     * Sertifikat: satu render = satu peserta; `LayananCetak` merender ulang
     * untuk setiap peserta saat bulk. SK: satu render = satu halaman/peserta.
     * Template contoh `contoh-sk-sbcc` memakai `$data`; `$baris` tetap tersedia
     * untuk template SK bertipe tabel.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $baris
     * @param  array<string, mixed>  $kop
     * @param  bool  $pakaiBackground  false untuk menyembunyikan gambar latar template
     */
    public static function renderBlade(
        Blangko $blangko,
        array $data,
        ?string $varianKey = null,
        array $baris = [],
        array $kop = [],
        bool $pakaiBackground = true,
    ): string {
        $view = (string) $blangko->view_blade;

        if ($view === '' || ! view()->exists($view)) {
            return '<div style="padding:20mm; font-family:sans-serif; color:#b91c1c;">'
                .'Template Blade "'.e($view).'" tidak ditemukan di resources/views/blangko/.'
                .'</div>';
        }

        $kunci = $varianKey ?: self::VARIAN_PRESTASI;

        if (! $pakaiBackground) {
            // Template yang memakai $kop['background'] ikut menghilangkan latar.
            $kop['background'] = null;
        }

        return view($view, [
            'blangko' => $blangko,
            'data' => $data,
            'kop' => $kop,
            'baris' => $baris,
            'varian' => $kunci,
            'varianTeks' => self::teksVarian($blangko, $kunci, $data),
            'pakaiBackground' => $pakaiBackground,
        ])->render();
    }

    /**
     * Ambil isi varian teks untuk satu blangko (placeholder sudah disubstitusi).
     *
     * @param  array<string, mixed>  $data
     */
    public static function teksVarian(Blangko $blangko, ?string $varianKey, array $data = []): string
    {
        $key = $varianKey ?: self::VARIAN_PRESTASI;

        $varian = TeksVarian::query()
            ->where('blangko_id', $blangko->id)
            ->where('key', $key)
            ->first();

        $teks = (string) ($varian->isi ?? '');

        return $data === [] ? $teks : strtr($teks, self::pengganti($data));
    }

    /**
     * Identitas dokumen (kop) dari Pengaturan — dipakai kanvas & template Blade.
     *
     * @return array<string, string|null>
     */
    public static function kop(): array
    {
        return [
            'nama_kejuaraan' => (string) Setting::getValue('nama_kejuaraan', 'Sertifikat Kejuaraan'),
            'singkatan' => (string) Setting::getValue('singkatan_kejuaraan', ''),
            'penyelenggara' => (string) Setting::getValue('penyelenggara', ''),
            'tempat_tanggal' => (string) Setting::getValue('tempat_tanggal', ''),
            'nama_ttd' => (string) Setting::getValue('nama_ttd', ''),
            'jabatan_ttd' => (string) Setting::getValue('jabatan_ttd', ''),
            'kota_ttd' => (string) Setting::getValue('kota_ttd', ''),
            'logo_kiri' => Setting::pathGambar(Setting::getValue('logo_kiri')),
            'logo_kanan' => Setting::pathGambar(Setting::getValue('logo_kanan')),
            'background' => Setting::pathGambar(Setting::getValue('background_sertifikat')),
        ];
    }

    /**
     * Render satu elemen.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $baris
     */
    private static function renderElemen(
        BlangkoElemen $elemen,
        array $data,
        ?string $varianKey,
        array $baris,
    ): string {
        if (! $elemen->tampil) {
            return '';
        }

        $isi = match ($elemen->tipe) {
            'data' => (string) ($data[$elemen->sumber_field] ?? '-'),
            'varian' => self::isiVarian($elemen, $varianKey, $data),
            'tabel' => self::renderTabel($baris, $elemen),
            default => (string) $elemen->isi,
        };

        if ($elemen->tipe !== 'tabel') {
            $isi = strtr($isi, self::pengganti($data));
        }

        $font = match ($elemen->font_family) {
            'serif' => '"DejaVu Serif", serif',
            'mono' => '"DejaVu Mono", monospace',
            default => '"DejaVu Sans", sans-serif',
        };

        $style = sprintf(
            'position:absolute; left:%dmm; top:%dmm; width:%dmm; height:%dmm; z-index:%d;'
            .' font-family:%s; font-size:%dpt; color:%s; text-align:%s; line-height:1.25;',
            $elemen->x_mm,
            $elemen->y_mm,
            $elemen->lebar_mm,
            $elemen->tinggi_mm,
            $elemen->z_index,
            $font,
            $elemen->font_size,
            $elemen->warna,
            $elemen->align,
        );

        if ($elemen->tebal) {
            $style .= ' font-weight:bold;';
        }

        if ($elemen->miring) {
            $style .= ' font-style:italic;';
        }

        $valign = match ($elemen->valign) {
            'middle' => 'middle',
            'bottom' => 'bottom',
            default => 'top',
        };

        $gaya = sprintf(
            'display:table; width:100%%; height:100%%;',
        );

        return sprintf(
            '<div class="blangko-elemen" data-elemen-id="%d" style="%s">'
            .'<div style="%s vertical-align:%s;">%s</div></div>',
            $elemen->id,
            $style,
            $gaya,
            $valign,
            $isi,
        );
    }

    /**
     * Peta placeholder ({NAMA}, {KONTINGEN}, ...) dari data konteks.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private static function pengganti(array $data): array
    {
        $peta = [];

        foreach ($data as $key => $value) {
            $peta['{'.strtoupper((string) $key).'}'] = (string) $value;
        }

        return $peta;
    }

    /**
     * Ambil isi varian teks (tipe 'varian').
     *
     * @param  array<string, mixed>  $data
     */
    private static function isiVarian(BlangkoElemen $elemen, ?string $varianKey, array $data): string
    {
        $key = $elemen->varian_key ?: ($varianKey ?: self::VARIAN_PRESTASI);

        $varian = TeksVarian::query()
            ->where('blangko_id', $elemen->blangko_id)
            ->where('key', $key)
            ->first();

        return (string) ($varian->isi ?? '');
    }

    /**
     * Render tabel dinamis (dipakai pada SK untuk daftar juara).
     *
     * @param  array<int, array<string, mixed>>  $baris
     */
    private static function renderTabel(array $baris, BlangkoElemen $elemen): string
    {
        if ($baris === []) {
            return '';
        }

        $html = '<table style="width:100%; border-collapse:collapse; font-size:'.(int) $elemen->font_size.'pt;">';

        foreach ($baris as $row) {
            $html .= '<tr>';

            foreach ($row as $sel) {
                $html .= '<td style="border:0.4pt solid #0f172a; padding:1mm 1.5mm;">'.e((string) $sel).'</td>';
            }

            $html .= '</tr>';
        }

        return $html.'</table>';
    }
}
