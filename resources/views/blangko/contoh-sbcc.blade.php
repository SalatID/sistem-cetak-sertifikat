{{--
    Template sertifikat — SATU RENDER = SATU PESERTA.

    Bulk cetak ditangani oleh LayananCetak: template ini dirender ulang untuk
    setiap peserta yang dipilih, lalu semua halaman digabung menjadi satu PDF.
    Jadi jangan mengubah $data menjadi daftar; gunakan $data sebagai satu peserta.
--}}
<html>

<head>
    @php
        // Pakai path file, bukan base64. Pada cetak bulk, base64 akan disalin
        // ke setiap halaman sehingga HTML membengkak dan DOMPDF kehabisan memori.
        $pathBackground = str_replace('\\', '/', public_path('blangko-assets/sbckc2.jpg'));
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
                           <p class="w-100 text-center" style="font-size:50px;padding-top:1cm;font-weight:bold;">
                                   @if ($pakaiBackground)
                                       @if(strtolower($data->kelompok_usia)=="usia dini 1")
                                           SERTIFIKAT PEMASALAN
                                       @else
                                           SERTIFIKAT PRESTASI
                                       @endif
                                      @else
                                      &nbsp;
                                    @endif
                           </p>
                           <p class="w-100 text-center" style="font-size:15px;margin-top:-1cm">
                               @if (strtolower($data->juara) != 'peserta' && strtolower($data->juara) != '-')
                                   Nomor : B/HK.08.03/10.15.{{ strtoupper($data->no_sertifikat) }}/SET/X/2026
                                   @else
                                   Nomor : 030/SBCKC/II/X/2026
                               @endif
                           </p>
                       </div>
                   </div>
                   <div style="padding-top:2cm;">
                       <div class="w-100" style="float:left">
                           <div class="row justify-content-center">
                               <h1 class="w-100 text-center" style="font-size:25px;">
                                   {{ strtoupper($data->nama) }}
                               </h1>
                           </div>
                       </div>
                   </div>
                   <div style="padding-top:1.5cm;">
                       <div class="w-100" style="float:left">
                           <div class="row justify-content-center">
                               <h1 class="w-100 text-center" style="font-size:25px;">
                                @if($data->juara =="-" && $data->kategori == "-")
                                {{strtoupper($data->jabatan)}}  {{strtoupper($data->kontingen)}}
                                @else
                                   {{ strtoupper($data->juara)<=3 ? "JUARA " . $data->juara : $data->juara }} KATEGORI {{strtoupper($kategori)}} {{str_replace("2","",str_replace("1","",strtoupper($data->kelompok_usia)))}} {{ strtoupper($data->jenis_kelamin_teks) }}
                                   @endif
                               </h1>
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
