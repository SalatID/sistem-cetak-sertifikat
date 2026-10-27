{{--
    Template SK Juara contoh (mode Blade).

    Kontrak:
    - Tulis hanya ISI HALAMAN (satu div berukuran mm). Jangan sertakan <html>/<body>,
      sistem yang membungkus dan mengatur @page.
    - $baris berisi daftar baris juara: [nama, kontingen, kategori, kelas, juara].

    Catatan: satu nomor SK = satu halaman. Isi blok memakai posisi absolut agar
    tidak pernah meluber ke halaman kedua; jika baris juara terlalu banyak,
    perkecil font atau pecah nomor SK.
--}}
<style>
    .sk-halaman {
        position: relative;
        width: {{ $blangko->lebar_mm }}mm;
        height: {{ $blangko->tinggi_mm }}mm;
        overflow: hidden;
        background-color: #ffffff;
        font-family: "DejaVu Serif", serif;
        color: #0f172a;
        box-sizing: border-box;
    }
    .sk-kop { position: absolute; top: 18mm; left: 18mm; width: 174mm; text-align: center; font-size: 10pt; line-height: 1.45; border-bottom: 1.2pt solid #0f172a; padding-bottom: 4mm; }
    .sk-kop .penyelenggara { font-size: 9pt; text-transform: uppercase; letter-spacing: 1pt; }
    .sk-kop .nama-kejuaraan { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
    .sk-kop .tempat { font-size: 9pt; }
    .sk-judul { position: absolute; top: 50mm; left: 18mm; width: 174mm; text-align: center; font-size: 14pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1pt; }
    .sk-nomor { position: absolute; top: 60mm; left: 18mm; width: 174mm; text-align: center; font-size: 10pt; }
    .sk-isi { position: absolute; top: 72mm; left: 18mm; width: 174mm; font-size: 10.5pt; line-height: 1.7; text-align: justify; }
    .sk-tabel { position: absolute; top: 96mm; left: 18mm; width: 174mm; border-collapse: collapse; font-size: 9.5pt; }
    .sk-tabel th, .sk-tabel td { border: 0.5pt solid #0f172a; padding: 1.8mm 2mm; vertical-align: top; }
    .sk-tabel th { background-color: #e2e8f0; text-align: center; font-size: 9pt; text-transform: uppercase; }
    .sk-ttd { position: absolute; bottom: 20mm; right: 18mm; width: 70mm; text-align: center; font-size: 10pt; line-height: 1.5; }
    .sk-ttd .ruang { height: 20mm; }
    .sk-ttd .nama { font-weight: bold; text-transform: uppercase; text-decoration: underline; }
</style>

<div class="sk-halaman">
    <div class="sk-kop">
        @if (! empty($kop['penyelenggara']))
            <div class="penyelenggara">{{ $kop['penyelenggara'] }}</div>
        @endif
        <div class="nama-kejuaraan">{{ $kop['nama_kejuaraan'] }}</div>
        @if (! empty($kop['tempat_tanggal']))
            <div class="tempat">{{ $kop['tempat_tanggal'] }}</div>
        @endif
    </div>

    <div class="sk-judul">Surat Keputusan Juara</div>
    <div class="sk-nomor">Nomor: {{ $data['no_sk'] }}</div>

    <div class="sk-isi">
        Berdasarkan hasil pertandingan pada {{ $kop['nama_kejuaraan'] }}, dengan ini ditetapkan para juara
        sebagai berikut:
    </div>

    <table class="sk-tabel">
        <thead>
            <tr>
                <th style="width: 8mm;">No</th>
                <th>Nama</th>
                <th>Kontingen</th>
                <th>Kategori</th>
                <th style="width: 22mm;">Kelas</th>
                <th style="width: 16mm;">Juara</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $index => $row)
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td>{{ $row[0] }}</td>
                    <td>{{ $row[1] }}</td>
                    <td>{{ $row[2] }}</td>
                    <td>{{ $row[3] !== '' ? $row[3] : '—' }}</td>
                    <td style="text-align:center;">{{ $row[4] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">Belum ada peserta pada nomor SK ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sk-ttd">
        <div>{{ $kop['kota_ttd'] ? $kop['kota_ttd'].', ' : '' }}{{ $data['tanggal'] }}</div>
        <div>{{ $kop['jabatan_ttd'] }}</div>
        <div class="ruang"></div>
        <div class="nama">{{ $kop['nama_ttd'] }}</div>
    </div>
</div>
