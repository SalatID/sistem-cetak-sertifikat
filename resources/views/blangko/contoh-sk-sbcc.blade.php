{{--
    Template SK — SATU RENDER = SATU HALAMAN / SATU PESERTA.

    $data  = peserta pada halaman ini (dipakai oleh format SK ini)
    $baris = tetap tersedia untuk kompatibilitas template lain.

    Nomor SK boleh kosong; halaman tetap dicetak dengan nomor kosong.
--}}
<html>

<head>
    @php
        // Pakai path file, bukan base64, agar cetak bulk tidak membengkakkan HTML.
        $pathBackground = str_replace('\\', '/', public_path('blangko-assets/sk-sbckc2.jpg'));
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
            font-size: 12px;
            font-family: Arial, Helvetica, sans-serif;
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
            /* background-image: url(/blangko_sertifikat/sbck.jpg); */
            background-image: url('{{ $pathBackground }}');
            background-size: contain;
            background-repeat: no-repeat;
        }

        td{
            font-size:14px !important;
            color: black;
        }

        @media print {
            hr {
                background: black !important;
                -webkit-print-color-adjust: exact;
            }

            .blangko {
                /* background-image: url(/blangko_sertifikat/sbck.jpg); */
                background-size: contain;
                background-repeat: no-repeat;

            }

            body {
                width: 21cm !important;
                height: 29cm !important;
                display: table;
            }
        }
        @page { margin: 12px; }
    </style>
</head>

<body style="padding:0;margin:0;">
    {{-- Satu render = satu halaman/peserta; format ini dipertahankan. --}}
    <div class="text-center d-table mb-3 blangko" style="display: inline-block;position: relative; width:100%;height:96%;">
        <div style="position:absolute;top:41.5mm;left:60mm;width:90mm;height:8mm;background:#fff;text-align:center;font-size:11pt;line-height:8mm;">
            {{-- Nomor : {{ $data['no_sk'] ?? '' }} --}}
        </div>
        @php
            $kategori = $data['kategori'] ?? '';
        @endphp
        <div style="padding-top:18.2cm;margin-left:5.5cm;">
            <div class="w-100" style="float:left">
                <table>
                    <tr>
                        <td>Nama</td>
                        <td>:</td>
                        <td>{{ ucwords(strtolower($data['nama'])) }}</td>
                    </tr>
                    <tr>
                        <td>Juara</td>
                        <td>:</td>
                        <td>{{ ucwords(strtolower($data['juara'])) <= 3 ? "Juara " . $data['juara'] : ucwords(strtolower($data['juara'])) }} Kategori {{ ucwords(strtolower($kategori)) }} {{ str_replace("2","",str_replace("1","",ucwords(strtolower($data['kelompok_usia'])))) }} {{ ucwords(strtolower($data['jenis_kelamin']=="L"?"Putra":"Putri")) }}</td>
                    </tr>
                    <tr>
                        <td>Pelaksanaan</td>
                        <td>:</td>
                        <td>Jumat s.d Minggu, 16 s.d 18 Oktober 2026</td>
                    </tr>
                    <tr>
                        <td>Tempat</td>
                        <td>:</td>
                        <td>Mall Bale Kota, Kota tangerang</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
