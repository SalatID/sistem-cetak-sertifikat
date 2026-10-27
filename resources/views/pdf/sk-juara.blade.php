@extends('pdf.layouts.base-pdf')

@section('title', 'SK Juara')

@section('styles')
    @page { size: A4 portrait; margin: 0; }

    body {
        font-family: "DejaVu Serif", serif;
        color: #0f172a;
        margin: 0;
    }

    .halaman {
        position: relative;
        width: 210mm;
        height: 297mm;
        overflow: hidden;
        page-break-after: always;
    }

    .halaman:last-child { page-break-after: auto; }

    .logo-kiri  { position: absolute; top: 14mm; left: 15mm;  width: 22mm; }
    .logo-kanan { position: absolute; top: 14mm; right: 15mm; width: 22mm; }

    .kop-teks {
        position: absolute;
        top: 15mm; left: 45mm; width: 120mm;
        text-align: center;
        font-size: 10pt;
        line-height: 1.35;
    }

    .kop-teks .penyelenggara { font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.8pt; }
    .kop-teks .nama-kejuaraan { font-size: 12.5pt; font-weight: bold; text-transform: uppercase; }
    .kop-teks .tempat { font-size: 8.5pt; }

    .kop-bawah { border-bottom: 0.6pt solid #0f172a; }

    .nomor { margin-top: 5mm; font-size: 10pt; text-align: right; }

    .judul {
        margin: 5mm 0 0 0;
        text-align: center;
        font-size: 13pt;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: underline;
        line-height: 1.4;
    }

    .isi { margin: 6mm 20mm 0 20mm; font-size: 10.5pt; line-height: 1.7; text-align: justify; }
    .isi .paragraf { margin-bottom: 3mm; }

    .tabel {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5pt;
        margin-top: 3mm;
    }

    .tabel th, .tabel td { border: 0.4pt solid #0f172a; padding: 1.6mm 2mm; }
    .tabel th { background-color: #e2e8f0; text-align: center; font-size: 9pt; text-transform: uppercase; }
    .tabel td.pusat { text-align: center; }

    .ditetapkan { margin: 5mm 20mm 0 20mm; font-size: 10.5pt; line-height: 1.7; }

    .ttd {
        margin: 8mm 20mm 0 0;
        width: 80mm;
        margin-left: auto;
        text-align: center;
        font-size: 10.5pt;
        line-height: 1.5;
    }

    .ttd .ruang { height: 20mm; }
    .ttd .nama { font-weight: bold; text-transform: uppercase; }
@endsection

@section('content')
    @foreach ($kelompok as $group)
        <div class="halaman">
            @if (! empty($kop['logo_kiri']))
                <img src="{{ $kop['logo_kiri'] }}" class="logo-kiri" alt="">
            @endif
            @if (! empty($kop['logo_kanan']))
                <img src="{{ $kop['logo_kanan'] }}" class="logo-kanan" alt="">
            @endif

            <div class="kop-teks">
                @if (! empty($kop['penyelenggara']))
                    <div class="penyelenggara">{{ $kop['penyelenggara'] }}</div>
                @endif
                <div class="nama-kejuaraan">{{ $kop['nama_kejuaraan'] }}</div>
                @if (! empty($kop['tempat_tanggal']))
                    <div class="tempat">{{ $kop['tempat_tanggal'] }}</div>
                @endif
            </div>

            <div style="margin: 42mm 15mm 0 15mm;">
                <div class="kop-bawah"></div>
                <div class="nomor">Nomor: {{ $group['nomor'] }}</div>

                <div class="judul">
                    Surat Keputusan Ketua Panitia<br>
                    Kejuaraan {{ $kop['nama_kejuaraan'] }}
                </div>

                <div class="isi">
                    <div class="paragraf">
                        Menimbang, bahwa peserta yang nama-namanya tercantum dalam Daftar Peserta di bawah ini
                        telah mengikuti dan menyelesaikan seluruh pertandingan pada Kejuaraan
                        {{ $kop['nama_kejuaraan'] }};
                    </div>
                    <div class="paragraf">
                        Mengingat, bahwa berdasarkan hasil penilaian juri, peserta tersebut dinyatakan sebagai juara
                        pada kategori dan kelas yang tercantum dalam daftar;
                    </div>
                </div>

                <div class="isi" style="margin: 4mm 20mm 0 20mm;">
                    Maka perlu ditetapkan dan ditetapkan sebagai berikut:
                </div>

                <table class="tabel">
                    <thead>
                        <tr>
                            <th style="width: 10mm;">No</th>
                            <th>Nama Peserta</th>
                            <th style="width: 38mm;">Kontingen</th>
                            <th style="width: 40mm;">Kategori</th>
                            <th style="width: 24mm;">Kelas</th>
                            <th style="width: 18mm;">Juara</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group['peserta'] as $index => $p)
                            <tr>
                                <td class="pusat">{{ $index + 1 }}</td>
                                <td>{{ $p->nama }}</td>
                                <td>{{ $p->kontingen?->nama }}</td>
                                <td>{{ $p->kategori }}</td>
                                <td class="pusat">{{ $p->kelas ?: '—' }}</td>
                                <td class="pusat">{{ $p->juara }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="ditetapkan">
                    Ditetapkan di {{ $kop['kota_ttd'] ?: '................' }},
                    pada tanggal {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}.
                </div>

                <div class="ttd">
                    <div>{{ $kop['jabatan_ttd'] ?: 'Ketua Panitia' }}</div>
                    <div class="ruang"></div>
                    <div class="nama">{{ $kop['nama_ttd'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
