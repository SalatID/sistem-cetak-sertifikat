{{--
    Template sertifikat contoh (mode Blade).

    Kontrak:
    - Tulis hanya ISI HALAMAN (satu div berukuran mm). Jangan sertakan <html>/<body>,
      sistem yang membungkus dan mengatur @page.
    - Variabel: $data, $kop, $varian, $varianTeks, $baris (SK), $blangko.
--}}
@php
    $judul = $varian === 'pemasalan' ? 'SERTIFIKAT PEMASALAN' : 'SERTIFIKAT PRESTASI';
@endphp

<style>
    .sertifikat-halaman {
        position: relative;
        width: {{ $blangko->lebar_mm }}mm;
        height: {{ $blangko->tinggi_mm }}mm;
        overflow: hidden;
        background-color: #ffffff;
        font-family: "DejaVu Sans", sans-serif;
        color: #0f172a;
        box-sizing: border-box;
    }
    .sertifikat-bingkai-luar { position: absolute; top: 8mm; left: 8mm; width: 281mm; height: 194mm; border: 1.4pt solid #1e3a8a; }
    .sertifikat-bingkai-dalam { position: absolute; top: 11mm; left: 11mm; width: 275mm; height: 188mm; border: 0.4pt solid #1e3a8a; }
    .sertifikat-logo-kiri { position: absolute; top: 17mm; left: 20mm; width: 26mm; }
    .sertifikat-logo-kanan { position: absolute; top: 17mm; right: 20mm; width: 26mm; }
    .sertifikat-kop { position: absolute; top: 18mm; left: 52mm; width: 193mm; text-align: center; font-size: 10pt; line-height: 1.45; }
    .sertifikat-kop .penyelenggara { font-size: 9pt; text-transform: uppercase; letter-spacing: 1pt; }
    .sertifikat-kop .nama-kejuaraan { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
    .sertifikat-kop .tempat { font-size: 9pt; }
    .sertifikat-nomor { position: absolute; top: 44mm; right: 22mm; font-size: 9pt; text-align: right; }
    .sertifikat-judul { position: absolute; top: 58mm; left: 30mm; width: 237mm; text-align: center; font-size: 26pt; font-weight: bold; letter-spacing: 4pt; color: #1e3a8a; }
    .sertifikat-garis { position: absolute; top: 74mm; left: 100mm; width: 97mm; border-bottom: 0.6pt solid #1e3a8a; }
    .sertifikat-diberikan { position: absolute; top: 80mm; left: 30mm; width: 237mm; text-align: center; font-size: 11pt; }
    .sertifikat-nama { position: absolute; top: 90mm; left: 25mm; width: 247mm; text-align: center; font-size: 22pt; font-weight: bold; text-transform: uppercase; }
    .sertifikat-nama-garis { position: absolute; top: 106mm; left: 80mm; width: 137mm; border-bottom: 0.5pt solid #334155; }
    .sertifikat-deskripsi { position: absolute; top: 111mm; left: 45mm; width: 207mm; text-align: center; font-size: 11.5pt; line-height: 1.6; }
    .sertifikat-kategori { position: absolute; top: 137mm; left: 45mm; width: 207mm; text-align: center; font-size: 11pt; font-weight: bold; }
    .sertifikat-kota { position: absolute; bottom: 20mm; left: 22mm; width: 90mm; font-size: 10pt; line-height: 1.5; }
    .sertifikat-ttd { position: absolute; bottom: 20mm; right: 22mm; width: 80mm; text-align: center; font-size: 10pt; line-height: 1.5; }
    .sertifikat-ttd .ruang { height: 18mm; }
    .sertifikat-ttd .nama { font-size: 10pt; font-weight: bold; text-transform: uppercase; }
    .sertifikat-ttd .jabatan { font-size: 9.5pt; }
</style>

<div class="sertifikat-halaman">
    @if (! empty($kop['background']))
        <img src="{{ $kop['background'] }}" style="position:absolute; top:0; left:0; width:{{ $blangko->lebar_mm }}mm; height:{{ $blangko->tinggi_mm }}mm;" alt="">
    @endif

    <div class="sertifikat-bingkai-luar"></div>
    <div class="sertifikat-bingkai-dalam"></div>

    @if (! empty($kop['logo_kiri']))
        <img src="{{ $kop['logo_kiri'] }}" class="sertifikat-logo-kiri" alt="">
    @endif
    @if (! empty($kop['logo_kanan']))
        <img src="{{ $kop['logo_kanan'] }}" class="sertifikat-logo-kanan" alt="">
    @endif

    <div class="sertifikat-kop">
        @if (! empty($kop['penyelenggara']))
            <div class="penyelenggara">{{ $kop['penyelenggara'] }}</div>
        @endif
        <div class="nama-kejuaraan">{{ $kop['nama_kejuaraan'] }}</div>
        @if (! empty($kop['tempat_tanggal']))
            <div class="tempat">{{ $kop['tempat_tanggal'] }}</div>
        @endif
    </div>

    <div class="sertifikat-nomor">No. {{ $data['no_sertifikat'] }}</div>

    <div class="sertifikat-judul">{{ $judul }}</div>
    <div class="sertifikat-garis"></div>

    <div class="sertifikat-diberikan">Diberikan kepada:</div>
    <div class="sertifikat-nama">{{ $data['nama'] }}</div>
    <div class="sertifikat-nama-garis"></div>

    <div class="sertifikat-deskripsi">{{ $varianTeks }}</div>

    <div class="sertifikat-kategori">
        {{ $data['kategori'] }}@if (! empty($data['kelas'])) &mdash; {{ $data['kelas'] }}@endif
        @if (! empty($data['kelompok_usia'])) ({{ $data['kelompok_usia'] }})@endif
    </div>

    <div class="sertifikat-kota">
        {{ $kop['kota_ttd'] ? $kop['kota_ttd'].', ' : '' }}{{ $data['tanggal'] }}
    </div>

    <div class="sertifikat-ttd">
        <div class="jabatan">{{ $kop['jabatan_ttd'] }}</div>
        <div class="ruang"></div>
        <div class="nama">{{ $kop['nama_ttd'] }}</div>
    </div>
</div>
