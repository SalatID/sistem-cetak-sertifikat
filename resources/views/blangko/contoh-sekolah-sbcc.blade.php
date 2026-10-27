{{--
    Template sertifikat — SATU RENDER = SATU PESERTA.

    Bulk cetak ditangani oleh LayananCetak: template ini dirender ulang untuk
    setiap peserta yang dipilih, lalu semua halaman digabung menjadi satu PDF.
    Jadi jangan mengubah $data menjadi daftar; gunakan $data sebagai satu peserta.
--}}
<html>

<head>
@php
    $pathBackground = str_replace('\\', '/', public_path('blangko-assets/sert-sekolah.jpg'));
    $pakaiBackground = $pakaiBackground ?? true;
    $namaSekolah = $data['nama'] ?? ($data['sekolah'] ?? '');
    $namaKejuaraan = trim((string) ($data['nama_kejuaraan'] ?? ''));
    $namaKejuaraan = $namaKejuaraan !== '' ? $namaKejuaraan : 'kejuaraan';
@endphp
    <title>Cetak Sertifikat</title>
    <link rel="stylesheet" href="{{ public_path() }}/assets/css/bootstrap.min.css">
    <script type="text/javascript" src="{{ public_path() }}/assets/js/jquery.js"></script>
    <style>
        /* Set height of the grid so .sidenav can be 100% (adjust if needed) */
        .row.content {
            height: 1500px
        }

        * {
            font-size: 18px;
        }

        /* Set black background color, white text and some padding */
        footer {
            background-color: #555;
            color: white;
            padding: 15px;
            position: 'fixed';
            bottom: 0;
            width: 100% !important;
            text-align: 'center';
        }

        .blangko {
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            text-align: center;
            box-sizing: border-box;
        }

        .w-100 {
            width: 100% !important;
            float: none !important;
        }

        .row {
            width: 100%;
        }

        .text-center {
            text-align: center !important;
        }

        .justify-content-center {
            text-align: center;
        }

        .pemasalan {
                background-image: url('{{ $pathBackground }}');
        }

        .prestasi {
            background-image: url('{{ $pathBackground }}');
        }

        .pemasalan-full {
            background-image: url('{{ $pathBackground }}');
        }

        .prestasi-full {
            background-image: url('{{ $pathBackground }}');
        }

        @media print {
            hr {
                background: black !important;
                -webkit-print-color-adjust: exact;
            }

            .blangko {
                /* background-image: url(/blangko_sertifikat/sbck.jpg); */
                background-position: center;
                background-size: 100% 100%;
                background-repeat: no-repeat;
            }

            .pemasalan {
                background-image: url('{{ $pathBackground }}');
            }

            .prestasi {
                background-image: url('{{ $pathBackground }}');
            }

            .pemasalan-full {
                background-image: url('{{ $pathBackground }}');
            }

            .prestasi-full {
                background-image: url('{{ $pathBackground }}');
            }


            body {
                width: 29.7cm !important;
                height: 21cm !important;
                display: table;
            }
        }
        @page { size: A4 landscape; margin: 0; }
    </style>
</head>

<body style="margin:0 auto;">
    {{-- @dd($data) --}}
    @php
        $data = is_array($data) ? (object) $data : $data;
        $kategori = $data->kategori ?? null;
        $pakaiBackground = $pakaiBackground ?? true;
    @endphp

    {{-- $data = satu peserta; perulangan bulk dilakukan oleh LayananCetak. --}}
    <div class="text-center d-table mb-3 blangko {{ $pakaiBackground ? ($data->kelompok_usia == 'USIA DINI 1' ? 'pemasalan' : 'prestasi') : '' }}" style="display: block;position: relative;width:29.7cm;height:21cm;{{ $pakaiBackground ? '' : 'background-image:none;' }}">
               <div class="w-100" style="float:left;padding-left:2.7cm;">
                   <div class="w-100" style="float:left">
                       <div class="row justify-content-center">
                           <p class="w-100 text-center" style="font-size:50px;padding-top:2cm;font-weight:bold;">
                                   @if ($pakaiBackground)
                                       PIAGAM PENGHARGAAN
                                      @else
                                      &nbsp;
                                    @endif
                           </p>
                           
                       </div>
                   </div>
                   <div style="padding-top:0.7cm;">
                       <div class="w-100" style="float:left">
                           <div class="row justify-content-center">
                               <h1 class="w-100 text-center" style="font-size:30px;">
                                   {{ strtoupper($namaSekolah) }}
                               </h1>
                           </div>
                       </div>
                   </div>
                   <div style="padding-top:0cm;">
                       <div class="w-100" style="float:left">
                           <div class="row justify-content-center">
                            <p>atas partisipasinya dalam</p>
                               <h1 class="w-100 text-center" style="font-size:30px;">
                               Silat Benteng Cisadane Kids Championship 2 Tingkat Nasional
                               </h1>
                               <p>yang diselenggarakan di Mall Balekota, Kota Tangerang</p>
                               <p>Pada 16-18 Oktober 2026</p>
                           </div>
                       </div>
                   </div>
                   <div style="padding-top:5cm;padding-right:14cm; display:none">
                       <div class="w-100" style="float:left">
                           <img width="200px" src="{{ public_path() }}/kejuaraan/ttd_menpora.jpg" alt="">
                       </div>
                   </div>
               </div>
            

            </div>
        {{-- Selesai satu peserta. --}}

</body>

</html>
