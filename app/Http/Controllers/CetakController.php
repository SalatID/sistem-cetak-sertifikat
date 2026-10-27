<?php

namespace App\Http\Controllers;

use App\Models\Official;
use App\Models\Peserta;
use App\Models\Sekolah;
use App\Services\LayananCetak;
use App\Services\NomorSertifikat;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

/**
 * Endpoint cetak PDF untuk user yang sudah login (panitia).
 */
class CetakController extends Controller
{
    private const MAKS_PESERTA = 50;

    public function __construct(private readonly LayananCetak $layanan) {}

    /**
     * Cetak sertifikat juara peserta (satuan maupun bulk).
     */
    public function sertifikatPeserta(Request $request): Response
    {
        $ids = $this->validasiIds($request);

        $pesertas = Peserta::with('kontingen')
            ->whereIn('id', $ids)
            ->orderBy('kategori')
            ->orderBy('juara')
            ->orderBy('nama')
            ->get();

        abort_if($pesertas->isEmpty(), 422, 'Tidak ada peserta terpilih untuk dicetak.');

        // Semua peserta yang sudah punya keterangan juara boleh dicetak.
        // Label "Peserta" tetap dicetak, tetapi tidak mendapat nomor sertifikat.
        $tidakBisaCetak = $pesertas->reject(fn (Peserta $p) => $p->bolehCetakSertifikatPublik());

        if ($tidakBisaCetak->isNotEmpty()) {
            $nama = $tidakBisaCetak->pluck('nama')->implode(', ');

            abort_if(
                $pesertas->count() === $tidakBisaCetak->count(),
                422,
                "Peserta berikut belum memiliki keterangan juara sehingga belum bisa dicetak: {$nama}."
            );
        }

        $pesertas = $pesertas->filter(fn (Peserta $p) => $p->bolehCetakSertifikatPublik())->values();

        if ($tidakBisaCetak->isNotEmpty()) {
            session()->flash('warning', 'Dilewati (belum memiliki keterangan juara): '.$tidakBisaCetak->pluck('nama')->implode(', ').'.');
        }

        // Penomoran lazy hanya untuk Juara 1/2/3 & Pesilat Terbaik; label Peserta tetap NULL.
        NomorSertifikat::assign($pesertas);

        // Admin boleh memilih cetak tanpa background; default tetap dengan background.
        $pakaiBackground = $request->boolean('pakai_background', true);

        return $this->layanan->sertifikat($pesertas, auth()->id(), $pakaiBackground);
    }

    /**
     * Cetak SK Juara. Satu peserta = satu halaman SK.
     */
    public function skJuara(Request $request): Response
    {
        $ids = $this->validasiIds($request);

        $pesertas = Peserta::with('kontingen')
            ->whereIn('id', $ids)
            ->orderBy('kategori')
            ->orderBy('juara')
            ->orderBy('nama')
            ->get();

        abort_if($pesertas->isEmpty(), 422, 'Tidak ada peserta terpilih untuk dicetak.');

        // No. SK boleh kosong; setiap peserta tetap mendapat satu halaman SK.
        return $this->layanan->sk($pesertas, auth()->id());
    }

    /**
     * Cetak sertifikat coach/official.
     */
    public function sertifikatOfficial(Request $request, Official $official): Response
    {
        $pakaiBackground = $request->boolean('pakai_background', true);

        return $this->layanan->sertifikatOfficial($official, auth()->id(), $pakaiBackground);
    }

    /**
     * Cetak sertifikat ucapan terima kasih untuk sekolah (khusus admin).
     */
    public function sertifikatSekolah(Sekolah $sekolah): Response
    {
        return $this->layanan->sertifikatSekolah($sekolah, auth()->id());
    }

    /**
     * Validasi pilihan peserta untuk endpoint cetak.
     *
     * Berbeda dengan form biasa, endpoint cetak menjawab dengan **422** (bukan redirect),
     * karena request datang dari tombol cetak (form GET) — pesan error harus terlihat langsung.
     *
     * @return list<int>
     */
    private function validasiIds(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAKS_PESERTA],
            'ids.*' => ['integer', 'exists:pesertas,id'],
        ], [
            'ids.required' => 'Pilih minimal satu peserta untuk dicetak.',
            'ids.max' => 'Maksimal '.self::MAKS_PESERTA.' peserta per cetakan.',
            'ids.*.exists' => 'Ada peserta yang dipilih yang tidak ditemukan.',
        ]);

        abort_if($validator->fails(), 422, $validator->errors()->first());

        return array_map('intval', $validator->validated()['ids']);
    }
}
