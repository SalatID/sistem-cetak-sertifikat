<?php

namespace App\Services;

use App\Models\Blangko;
use App\Models\Official;
use App\Models\Peserta;
use App\Models\PrintLog;
use App\Models\Sekolah;
use App\Models\Setting;
use App\Models\StempelLegalisir;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Layanan pencetakan dokumen (dipakai oleh admin & tamu/guest).
 */
class LayananCetak
{
    public const MAKS_COPY_LEGALISIR = 20;

    /**
     * Jumlah copy legalisir dari Pengaturan (default 2, dibatasi 1..20).
     */
    public static function jumlahCopyLegalisir(): int
    {
        $nilai = (int) Setting::getValue('legalisir_jumlah_copy', 2);

        return max(1, min(self::MAKS_COPY_LEGALISIR, $nilai > 0 ? $nilai : 2));
    }

    /**
     * Cetak sertifikat peserta (satuan maupun bulk).
     *
     * Pemanggil bertanggung jawab memfilter peserta yang boleh dicetak.
     * Label "Peserta" boleh dicetak tanpa nomor sertifikat.
     *
     * @param  Collection<int, Peserta>  $pesertas
     * @param  bool  $pakaiBackground  false untuk cetak tanpa gambar latar (khusus admin).
     *                                 Tamu selalu mencetak dengan background.
     */
    public function sertifikat(
        Collection $pesertas,
        ?int $userId = null,
        bool $pakaiBackground = true,
    ): Response {
        abort_if($pesertas->isEmpty(), 422, 'Tidak ada peserta untuk dicetak.');

        $blangko = Blangko::aktif(Blangko::JENIS_SERTIFIKAT);

        if ($blangko) {
            if ($blangko->pakaiBlade()) {
                $halaman = [];

                foreach ($pesertas as $peserta) {
                    $halaman[] = BlangkoRenderer::renderBlade(
                        $blangko,
                        BlangkoRenderer::dataPeserta($peserta),
                        BlangkoRenderer::varianUntuk($peserta->kelompok_usia),
                        [],
                        $this->kop(),
                        $pakaiBackground,
                    );
                }

                $pdf = Pdf::loadHTML($this->dokumenBlade($blangko, $halaman));
            } else {
                $html = $this->styleHalaman($blangko);

                foreach ($pesertas as $peserta) {
                    $varian = BlangkoRenderer::varianUntuk($peserta->kelompok_usia);

                    $html .= '<div style="page-break-after:always; margin:0;">'
                        .BlangkoRenderer::render(
                            $blangko,
                            BlangkoRenderer::dataPeserta($peserta),
                            $pakaiBackground ? $blangko->pathGambar() : null,
                            $varian,
                        )
                        .'</div>';
                }

                $pdf = Pdf::loadHTML($html);
            }
        } else {
            $pdf = Pdf::loadView('pdf.sertifikat-peserta', [
                'pesertas' => $pesertas->map(fn (Peserta $p) => $this->dataSertifikat($p))->all(),
                'kop' => $this->kop(),
                'background' => $pakaiBackground
                    ? Setting::pathGambar(Setting::getValue('background_sertifikat'))
                    : null,
            ])->setPaper('a4', 'landscape');
        }

        $this->catatCetak(
            jenis: 'sertifikat_peserta',
            jumlah: $pesertas->count(),
            konteks: $pesertas->pluck('nama')->implode(', '),
            userId: $userId,
            pesertaId: $pesertas->count() === 1 ? $pesertas->first()->id : null,
            kontingenId: $pesertas->count() === 1 ? $pesertas->first()->kontingen_id : null,
        );

        return $pdf->stream('sertifikat-juara-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Cetak legalisir sertifikat peserta: hitam putih, satu stempel, dan
     * sejumlah copy sesuai Pengaturan.
     *
     * @param  Collection<int, Peserta>  $pesertas
     */
    public function legalisir(
        Collection $pesertas,
        StempelLegalisir $stempel,
        ?int $userId = null,
    ): Response {
        abort_if($pesertas->isEmpty(), 422, 'Tidak ada peserta untuk dicetak.');
        abort_if(! $stempel->aktif, 422, 'Stempel legalisir tidak aktif.');
        abort_if($stempel->pathGambar() === null, 422, 'Gambar stempel legalisir tidak ditemukan.');

        $blangko = Blangko::aktif(Blangko::JENIS_SERTIFIKAT);
        $lebar = (float) ($blangko?->lebar_mm ?? 297);
        $tinggi = (float) ($blangko?->tinggi_mm ?? 210);

        abort_if(
            $stempel->x_mm + $stempel->lebar_mm > $lebar + 0.01
            || $stempel->y_mm + $stempel->tinggi_mm > $tinggi + 0.01,
            422,
            'Posisi stempel melewati batas halaman sertifikat. Perbarui posisi stempel.'
        );

        $copy = self::jumlahCopyLegalisir();

        if ($blangko) {
            $halaman = [];

            foreach ($pesertas as $peserta) {
                $varian = BlangkoRenderer::varianUntuk($peserta->kelompok_usia);

                $isi = $blangko->pakaiBlade()
                    ? BlangkoRenderer::renderBlade(
                        $blangko,
                        BlangkoRenderer::dataPeserta($peserta),
                        $varian,
                        [],
                        $this->kop(),
                    )
                    : BlangkoRenderer::render(
                        $blangko,
                        BlangkoRenderer::dataPeserta($peserta),
                        $blangko->pathGambar(),
                        $varian,
                    );

                for ($i = 0; $i < $copy; $i++) {
                    $halaman[] = $isi;
                }
            }

            $html = $this->dokumenLegalisir($blangko, $halaman, $stempel);
            $pdf = Pdf::loadHTML(LegalisirRenderer::grayscaleHtml($html));
        } else {
            $data = [];

            foreach ($pesertas as $peserta) {
                $satu = $this->dataSertifikat($peserta);

                for ($i = 0; $i < $copy; $i++) {
                    $data[] = $satu;
                }
            }

            $html = view('pdf.sertifikat-peserta', [
                'pesertas' => $data,
                'kop' => $this->kop(),
                'background' => Setting::pathGambar(Setting::getValue('background_sertifikat')),
                'legalisir' => true,
                'stempel' => $this->dataStempel($stempel),
            ])->render();

            $pdf = Pdf::loadHTML(LegalisirRenderer::grayscaleHtml($html))
                ->setPaper('a4', 'landscape');
        }

        $this->catatCetak(
            jenis: 'legalisir',
            jumlah: $pesertas->count(),
            konteks: $pesertas->pluck('nama')->implode(', ').' · '.$copy.' copy · stempel '.$stempel->nama,
            userId: $userId,
            pesertaId: $pesertas->count() === 1 ? $pesertas->first()->id : null,
            kontingenId: $pesertas->count() === 1 ? $pesertas->first()->kontingen_id : null,
            jumlahCopy: $copy,
        );

        $namaStempel = Str::slug($stempel->nama) ?: 'stempel';

        return $pdf->stream('legalisir-'.$namaStempel.'-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Cetak SK Juara. Satu peserta = satu halaman SK, berapa pun `no_sk`-nya.
     *
     * @param  Collection<int, Peserta>  $pesertas
     */
    public function sk(Collection $pesertas, ?int $userId = null): Response
    {
        $pesertas = $pesertas->values();
        $blangko = Blangko::aktif(Blangko::JENIS_SK);

        if ($blangko) {
            if ($blangko->pakaiBlade()) {
                $halaman = [];

                foreach ($pesertas as $peserta) {
                    $baris = $this->barisTabel(collect([$peserta]));

                    $halaman[] = BlangkoRenderer::renderBlade(
                        $blangko,
                        BlangkoRenderer::dataPeserta($peserta),
                        BlangkoRenderer::VARIAN_PRESTASI,
                        $baris,
                        $this->kop(),
                    );
                }

                $pdf = Pdf::loadHTML($this->dokumenBlade($blangko, $halaman));
            } else {
                $html = $this->styleHalaman($blangko);

                foreach ($pesertas as $peserta) {
                    $baris = $this->barisTabel(collect([$peserta]));

                    $html .= '<div style="page-break-after:always; margin:0;">'
                        .BlangkoRenderer::render(
                            $blangko,
                            BlangkoRenderer::dataPeserta($peserta),
                            $blangko->pathGambar(),
                            BlangkoRenderer::VARIAN_PRESTASI,
                            $baris,
                        )
                        .'</div>';
                }

                $pdf = Pdf::loadHTML($html);
            }
        } else {
            $pdf = Pdf::loadView('pdf.sk-juara', [
                'kelompok' => $pesertas->map(fn (Peserta $peserta) => [
                    'nomor' => (string) $peserta->no_sk,
                    'peserta' => [$peserta],
                ])->values()->all(),
                'kop' => $this->kop(),
            ])->setPaper('a4', 'portrait');
        }

        foreach ($pesertas as $peserta) {
            $this->catatCetak(
                jenis: 'sk_juara',
                jumlah: 1,
                konteks: $peserta->nama,
                userId: $userId,
                nomorSk: (string) $peserta->no_sk,
            );
        }

        return $pdf->stream('sk-juara-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Cetak sertifikat coach/official.
     *
     * @param  bool  $pakaiBackground  false untuk cetak tanpa gambar latar (khusus admin).
     *                                 Tamu selalu mencetak dengan background.
     */
    public function sertifikatOfficial(
        Official $official,
        ?int $userId = null,
        bool $pakaiBackground = true,
    ): Response {
        if (! $official->relationLoaded('kontingen')) {
            $official->load('kontingen');
        }

        $blangko = Blangko::aktif(Blangko::JENIS_SERTIFIKAT);

        if ($blangko) {
            if ($blangko->pakaiBlade()) {
                $halaman = [
                    BlangkoRenderer::renderBlade(
                        $blangko,
                        BlangkoRenderer::dataOfficial($official),
                        BlangkoRenderer::VARIAN_PRESTASI,
                        [],
                        $this->kop(),
                        $pakaiBackground,
                    ),
                ];

                $pdf = Pdf::loadHTML($this->dokumenBlade($blangko, $halaman));
            } else {
                $html = $this->styleHalaman($blangko)
                    .BlangkoRenderer::render(
                        $blangko,
                        BlangkoRenderer::dataOfficial($official),
                        $pakaiBackground ? $blangko->pathGambar() : null,
                        BlangkoRenderer::VARIAN_PRESTASI,
                    );

                $pdf = Pdf::loadHTML($html);
            }
        } else {
            $teks = Setting::renderTemplate('teks_sertifikat_official', [
                '{NAMA}' => $official->nama,
                '{JABATAN}' => $official->jabatan,
                '{KONTINGEN}' => (string) $official->kontingen?->nama,
            ], 'Diberikan kepada {NAMA} selaku {JABATAN} Kontingen {KONTINGEN}.');

            $pdf = Pdf::loadView('pdf.sertifikat-official', [
                'official' => $official,
                'teks' => $teks,
                'kop' => $this->kop(),
                'background' => $pakaiBackground
                    ? Setting::pathGambar(Setting::getValue('background_sertifikat'))
                    : null,
            ])->setPaper('a4', 'landscape');
        }

        $this->catatCetak(
            jenis: 'sertifikat_official',
            jumlah: 1,
            konteks: $official->nama.' ('.$official->jabatan.')',
            userId: $userId,
            officialId: $official->id,
            kontingenId: $official->kontingen_id,
        );

        return $pdf->stream('sertifikat-official-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Cetak sertifikat ucapan terima kasih untuk satu sekolah.
     *
     * Sertifikat sekolah memakai blangko khusus `sertifikat_sekolah` dan tidak
     * memiliki nomor sertifikat. Bila belum ada blangko aktif, template bawaan
     * `pdf.sertifikat-sekolah` dipakai agar fitur tetap dapat dicetak.
     */
    public function sertifikatSekolah(Sekolah $sekolah, ?int $userId = null): Response
    {
        $blangko = Blangko::aktif(Blangko::JENIS_SEKOLAH);

        if ($blangko) {
            if ($blangko->pakaiBlade()) {
                $halaman = [
                    BlangkoRenderer::renderBlade(
                        $blangko,
                        BlangkoRenderer::dataSekolah($sekolah),
                        BlangkoRenderer::VARIAN_PRESTASI,
                        [],
                        $this->kop(),
                    ),
                ];

                $pdf = Pdf::loadHTML($this->dokumenBlade($blangko, $halaman));
            } else {
                $html = $this->styleHalaman($blangko)
                    .BlangkoRenderer::render(
                        $blangko,
                        BlangkoRenderer::dataSekolah($sekolah),
                        $blangko->pathGambar(),
                        BlangkoRenderer::VARIAN_PRESTASI,
                    );

                $pdf = Pdf::loadHTML($html);
            }
        } else {
            $pdf = Pdf::loadView('pdf.sertifikat-sekolah', [
                'sekolah' => $sekolah,
                'kop' => $this->kop(),
            ])->setPaper('a4', 'landscape');
        }

        $sekolah->forceFill([
            'terakhir_dicetak_at' => now(),
            'jumlah_cetak' => $sekolah->jumlah_cetak + 1,
        ])->save();

        $this->catatCetak(
            jenis: 'sertifikat_sekolah',
            jumlah: 1,
            konteks: $sekolah->nama,
            userId: $userId,
        );

        $namaFile = Str::of(Str::ascii($sekolah->nama))
            ->replaceMatches('~[\\\\/:*?"<>|]+~u', ' ')
            ->squish()
            ->toString();

        $namaFile = trim($namaFile, " .\t\n\r\0\x0B");

        if ($namaFile === '') {
            $namaFile = 'Sekolah';
        }

        return $pdf->stream(Str::limit($namaFile, 120, '').'.pdf');
    }

    /**
     * Style halaman untuk mode kanvas — ukuran mengikuti ukuran kertas blangko.
     */
    private function styleHalaman(Blangko $blangko): string
    {
        return '<style>@page { size: '.$blangko->lebar_mm.'mm '.$blangko->tinggi_mm.'mm; margin: 0; }'
            .' body { margin: 0; padding: 0; }</style>';
    }

    /**
     * Bungkus halaman-halaman hasil template Blade menjadi satu dokumen HTML.
     *
     * Ukuran `@page` mengikuti ukuran blangko (mm) sehingga template tidak perlu
     * mengatur ukuran kertas sendiri.
     *
     * @param  list<string>  $halaman
     */
    private function dokumenBlade(Blangko $blangko, array $halaman): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            .'@page { size: '.$blangko->lebar_mm.'mm '.$blangko->tinggi_mm.'mm; margin: 0; }'
            .' html, body { margin: 0; padding: 0; }'
            .'</style></head><body>';

        $total = count($halaman);

        foreach ($halaman as $index => $isi) {
            $break = $index < $total - 1 ? 'page-break-after: always;' : '';
            $html .= '<div style="'.$break.' margin: 0;">'.$isi.'</div>';
        }

        return $html.'</body></html>';
    }

    /**
     * Bungkus halaman legalisir (kanvas atau Blade) + satu stempel.
     *
     * @param  list<string>  $halaman
     */
    private function dokumenLegalisir(Blangko $blangko, array $halaman, StempelLegalisir $stempel): string
    {
        $css = '@page { size: '.$blangko->lebar_mm.'mm '.$blangko->tinggi_mm.'mm; margin: 0; }'
            .' html, body { margin:0; padding:0; background:#fff; }'
            .' .legalisir-halaman { position:relative; width:'.$blangko->lebar_mm.'mm; height:'.$blangko->tinggi_mm.'mm; overflow:hidden; page-break-after:always; }'
            .' .legalisir-halaman:last-child { page-break-after:auto; }'
            .' .legalisir-grayscale { position:absolute; top:0; left:0; width:100%; height:100%; }'
            .LegalisirRenderer::cssGrayscale()
            .' .stempel-legalisir { position:absolute; z-index:9999; opacity:0.85; }';

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'.$css.'</style></head><body>';

        foreach ($halaman as $isi) {
            $html .= '<div class="legalisir-halaman">'
                .'<div class="legalisir-grayscale">'.$isi.'</div>'
                .$this->stempelHtml($stempel)
                .'</div>';
        }

        return $html.'</body></html>';
    }

    /**
     * Tag gambar stempel. Atribut data-stempel-legalisir membuat renderer
     * grayscale tidak mengubah warna stempel.
     */
    private function stempelHtml(StempelLegalisir $stempel): string
    {
        $path = $stempel->pathGambar();

        if ($path === null) {
            return '';
        }

        return sprintf(
            '<img src="%s" class="stempel-legalisir" data-stempel-legalisir="1" alt=""'
            .' style="left:%smm; top:%smm; width:%smm; height:%smm;">',
            e(str_replace('\\', '/', $path)),
            $this->mm($stempel->x_mm),
            $this->mm($stempel->y_mm),
            $this->mm($stempel->lebar_mm),
            $this->mm($stempel->tinggi_mm),
        );
    }

    /**
     * Data stempel untuk template PDF bawaan.
     *
     * @return array<string, string>
     */
    private function dataStempel(StempelLegalisir $stempel): array
    {
        return [
            'nama' => $stempel->nama,
            'gambar' => str_replace('\\', '/', (string) $stempel->pathGambar()),
            'x_mm' => $this->mm($stempel->x_mm),
            'y_mm' => $this->mm($stempel->y_mm),
            'lebar_mm' => $this->mm($stempel->lebar_mm),
            'tinggi_mm' => $this->mm($stempel->tinggi_mm),
        ];
    }

    /**
     * Format milimeter tanpa nol desimal yang tidak perlu.
     */
    private function mm(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * Baris tabel juara untuk SK (dipakai kanvas & template Blade).
     *
     * @param  Collection<int, Peserta>  $items
     * @return list<list<string>>
     */
    private function barisTabel(Collection $items): array
    {
        return $items->map(fn (Peserta $p) => [
            $p->nama,
            (string) $p->kontingen?->nama,
            (string) $p->kategori,
            (string) $p->kelas,
            $p->juara !== null ? (string) $p->juara : '-',
        ])->values()->all();
    }

    /**
     * Data kop dokumen (identitas kejuaraan) dari Pengaturan.
     *
     * @return array<string, string|null>
     */
    private function kop(): array
    {
        return BlangkoRenderer::kop();
    }

    /**
     * Susun data satu peserta untuk template sertifikat bawaan.
     *
     * @return array<string, mixed>
     */
    private function dataSertifikat(Peserta $peserta): array
    {
        $nama = $peserta->nama;
        $juara = trim((string) $peserta->juara);
        $juaraRomawi = $peserta->juaraRomawi();
        $kategori = $peserta->kategori;
        $kelas = (string) $peserta->kelas;
        $kontingen = (string) $peserta->kontingen?->nama;
        $nomor = $peserta->no_sertifikat !== null
            ? str_pad((string) $peserta->no_sertifikat, 3, '0', STR_PAD_LEFT)
            : '';

        $teks = Setting::renderTemplate('teks_sertifikat', [
            '{NAMA}' => $nama,
            '{JUARA_ANGKA}' => $juara,
            '{JUARA_ROMAWI}' => $juaraRomawi,
            '{KATEGORI}' => $kategori,
            '{KELAS}' => $kelas === '' ? '' : ' - '.$kelas,
            '{KONTINGEN}' => $kontingen,
            '{NOMOR_SERTIFIKAT}' => $nomor,
        ], 'Diberikan kepada {NAMA} atas prestasinya sebagai JUARA {JUARA_ROMAWI} pada {KATEGORI}{KELAS} mewakili {KONTINGEN}.');

        return [
            'nama' => $nama,
            'juara_angka' => $juara,
            'juara_romawi' => $juaraRomawi,
            'kategori' => $kategori,
            'kelas' => $kelas,
            'kontingen' => $kontingen,
            'nomor' => $nomor,
            'teks' => $teks,
        ];
    }

    /**
     * Catat aksi cetak ke tabel print_logs (user_id kosong = cetak tamu).
     */
    private function catatCetak(
        string $jenis,
        int $jumlah,
        string $konteks,
        ?int $userId = null,
        ?int $pesertaId = null,
        ?int $officialId = null,
        ?int $kontingenId = null,
        ?string $nomorSk = null,
        ?int $jumlahCopy = null,
    ): void {
        PrintLog::create([
            'user_id' => $userId,
            'jenis_dokumen' => $jenis,
            'peserta_id' => $pesertaId,
            'official_id' => $officialId,
            'kontingen_id' => $kontingenId,
            'jumlah_data' => $jumlah,
            'jumlah_copy' => max(1, $jumlahCopy ?? 1),
            'nomor_sk' => $nomorSk,
            'konteks' => mb_substr($userId === null ? '[TAMU] '.$konteks : $konteks, 0, 500),
        ]);
    }
}
