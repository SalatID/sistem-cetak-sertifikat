@extends('pdf.layouts.base-pdf')

@section('title', 'Sertifikat Juara')

@section('styles')
    @page { size: A4 landscape; margin: 0; }

    body {
        font-family: "DejaVu Serif", serif;
        color: #0f172a;
        margin: 0;
    }

    .halaman {
        position: relative;
        width: 297mm;
        height: 210mm;
        overflow: hidden;
        page-break-after: always;
    }

    .halaman:last-child { page-break-after: auto; }

    .latar {
        position: absolute;
        top: 0;
        left: 0;
        width: 297mm;
        height: 210mm;
    }

    .bingkai-luar {
        position: absolute;
        top: 8mm; left: 8mm;
        width: 281mm; height: 194mm;
        border: 1.4pt solid #1e3a8a;
    }

    .bingkai-dalam {
        position: absolute;
        top: 11mm; left: 11mm;
        width: 275mm; height: 188mm;
        border: 0.4pt solid #1e3a8a;
    }

    .logo-kiri  { position: absolute; top: 17mm; left: 20mm;  width: 28mm; }
    .logo-kanan { position: absolute; top: 17mm; right: 20mm; width: 28mm; }

    .kop {
        position: absolute;
        top: 19mm; left: 58mm; width: 181mm;
        text-align: center;
        font-size: 10pt;
        line-height: 1.4;
    }

    .kop .penyelenggara { font-size: 9pt; text-transform: uppercase; letter-spacing: 1pt; }
    .kop .nama-kejuaraan { font-size: 14pt; font-weight: bold; text-transform: uppercase; }
    .kop .tempat { font-size: 9pt; }

    .nomor {
        position: absolute;
        top: 46mm; right: 20mm;
        font-size: 9pt;
        text-align: right;
    }

    .judul {
        position: absolute;
        top: 60mm; left: 30mm; width: 237mm;
        text-align: center;
        font-size: 28pt;
        font-weight: bold;
        letter-spacing: 5pt;
        color: #1e3a8a;
    }

    .garis-judul {
        position: absolute;
        top: 76mm; left: 105mm; width: 87mm;
        border-bottom: 0.6pt solid #1e3a8a;
    }

    .diberikan {
        position: absolute;
        top: 82mm; left: 30mm; width: 237mm;
        text-align: center;
        font-size: 11pt;
    }

    .nama {
        position: absolute;
        top: 92mm; left: 25mm; width: 247mm;
        text-align: center;
        font-size: 21pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #0f172a;
    }

    .nama-garis {
        position: absolute;
        top: 108mm; left: 85mm; width: 127mm;
        border-bottom: 0.5pt solid #334155;
    }

    .deskripsi {
        position: absolute;
        top: 113mm; left: 45mm; width: 207mm;
        text-align: center;
        font-size: 11.5pt;
        line-height: 1.6;
    }

    .kategori {
        position: absolute;
        top: 133mm; left: 45mm; width: 207mm;
        text-align: center;
        font-size: 11pt;
    }

    .kota {
        position: absolute;
        bottom: 22mm; left: 22mm; width: 90mm;
        font-size: 10pt;
        line-height: 1.5;
    }

    .ttd {
        position: absolute;
        bottom: 22mm; right: 22mm; width: 80mm;
        text-align: center;
        font-size: 10pt;
        line-height: 1.5;
    }

    .ttd .ruang { height: 18mm; }
    .ttd .nama { font-size: 10pt; font-weight: bold; text-transform: uppercase; }
    .ttd .jabatan { font-size: 9.5pt; }

    .nomor-sertifikat {
        position: absolute;
        bottom: 22mm; left: 50%;
        margin-left: -25mm; width: 50mm;
        text-align: center;
        font-size: 9.5pt;
    }
@endsection

@section('content')
    @foreach ($pesertas as $p)
        <div class="halaman">
            @if (! empty($background))
                <img src="{{ $background }}" class="latar" alt="">
            @endif

            <div class="bingkai-luar"></div>
            <div class="bingkai-dalam"></div>

            @if (! empty($kop['logo_kiri']))
                <img src="{{ $kop['logo_kiri'] }}" class="logo-kiri" alt="">
            @endif
            @if (! empty($kop['logo_kanan']))
                <img src="{{ $kop['logo_kanan'] }}" class="logo-kanan" alt="">
            @endif

            <div class="kop">
                @if (! empty($kop['penyelenggara']))
                    <div class="penyelenggara">{{ $kop['penyelenggara'] }}</div>
                @endif
                <div class="nama-kejuaraan">{{ $kop['nama_kejuaraan'] }}</div>
                @if (! empty($kop['tempat_tanggal']))
                    <div class="tempat">{{ $kop['tempat_tanggal'] }}</div>
                @endif
            </div>

            <div class="nomor">No. {{ $p['nomor'] }}</div>

            <div class="judul">SERTIFIKAT</div>
            <div class="garis-judul"></div>

            <div class="diberikan">Diberikan kepada:</div>
            <div class="nama">{{ $p['nama'] }}</div>
            <div class="nama-garis"></div>

            <div class="deskripsi">{{ $p['teks'] }}</div>

            <div class="kategori">
                @if ($p['kelas'] !== '')
                    {{ $p['kategori'] }} &mdash; {{ $p['kelas'] }}
                @else
                    {{ $p['kategori'] }}
                @endif
            </div>

            <div class="kota">
                {{ $kop['kota_ttd'] ? $kop['kota_ttd'].', ' : '' }}{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
            </div>

            <div class="ttd">
                <div>{{ $kop['jabatan_ttd'] }}</div>
                <div class="ruang"></div>
                <div class="nama">{{ $kop['nama_ttd'] }}</div>
            </div>

            <div class="nomor-sertifikat">No. {{ $p['nomor'] }}</div>
        </div>
    @endforeach
@endsection
