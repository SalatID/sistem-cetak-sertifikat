@extends('pdf.layouts.base-pdf')

@section('title', 'Sertifikat Sekolah')

@section('styles')
    @page { size: A4 landscape; margin: 0; }

    body {
        margin: 0;
        font-family: "DejaVu Sans", sans-serif;
        color: #0f172a;
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

    .judul {
        position: absolute;
        top: 20mm;
        left: 90mm;
        width: 190mm;
        text-align: center;
        font-size: 22pt;
        font-weight: bold;
        letter-spacing: 2pt;
        color: #c2410c;
    }

    .nama-sekolah {
        position: absolute;
        top: 68.5mm;
        left: 90mm;
        width: 190mm;
        height: 20mm;
        line-height: 20mm;
        text-align: center;
        font-size: 25pt;
        font-weight: bold;
        text-transform: uppercase;
        color: #ffffff;
        overflow: hidden;
    }

    .deskripsi {
        position: absolute;
        top: 94mm;
        left: 85mm;
        width: 200mm;
        text-align: center;
        font-size: 12pt;
        line-height: 1.6;
        color: #1f2937;
    }
@endsection

@section('content')
    @php
        $namaKejuaraan = trim((string) ($kop['nama_kejuaraan'] ?? ''));
        $namaKejuaraan = $namaKejuaraan !== '' ? $namaKejuaraan : 'kejuaraan';
    @endphp

    <div class="halaman">
        <img src="{{ public_path('blangko-assets/sert-sekolah.jpg') }}" class="latar" alt="">

        <div class="judul">SERTIFIKAT UCAPAN TERIMA KASIH</div>
        <div class="nama-sekolah">{{ $sekolah->nama }}</div>
        <div class="deskripsi">
            Atas partisipasinya dalam {{ $namaKejuaraan }}.
        </div>
    </div>
@endsection
