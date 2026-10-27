<?php

namespace App\Http\Controllers;

use App\Models\Kontingen;
use App\Models\Official;
use App\Models\Peserta;
use App\Services\LayananCetak;
use App\Services\NomorSertifikat;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Cetak dokumen TANPA login (tamu / peserta / pelatih).
 *
 * Dibatasi rate limiter agar tidak bisa dipakai untuk mengambil data massal.
 */
class CetakPublikController extends Controller
{
    public function __construct(private readonly LayananCetak $layanan) {}

    /**
     * Daftar peserta untuk tamu (paginasinya 15 per halaman, dapat dicari nama).
     */
    public function index(Request $request): View
    {
        $cari = $request->string('q')->toString();

        $peserta = Peserta::with('kontingen')
            ->when($cari !== '', fn ($query) => $query->where('nama', 'like', "%{$cari}%"))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('cetak-publik.index', [
            'peserta' => $peserta,
            'cari' => $cari,
        ]);
    }

    /**
     * Cetak sertifikat seorang peserta (tanpa login).
     */
    public function sertifikat(Peserta $peserta): Response
    {
        abort_unless(
            $peserta->bolehCetakSertifikatPublik(),
            404,
            'Sertifikat tersedia untuk peserta yang memiliki keterangan juara.'
        );

        $peserta->load('kontingen');

        // Juara 1/2/3 dan Pesilat Terbaik mendapat nomor MAX+1 saat pertama
        // dicetak. Label "Peserta" tetap boleh dicetak, tetapi nomornya
        // dibiarkan NULL.
        if ($peserta->berhakNomorSertifikat()) {
            NomorSertifikat::assign(collect([$peserta]));
        }

        // Tamu selalu mencetak dengan background; parameter dari URL tidak dipakai.
        return $this->layanan->sertifikat(collect([$peserta]), null, true);
    }

    /**
     * Cetak SK untuk peserta tersebut (tanpa login).
     *
     * Satu peserta = satu halaman SK. Nomor SK boleh kosong.
     */
    public function sk(Peserta $peserta): Response
    {
        $peserta->load('kontingen');

        return $this->layanan->sk(collect([$peserta]), null);
    }

    /**
     * Formulir sertifikat untuk coach/official (tamu).
     */
    public function officialForm(): View
    {
        return view('cetak-publik.official', [
            'kontingens' => Kontingen::orderBy('nama')->get(),
            'jabatanOptions' => Official::jabatanOptions(),
        ]);
    }

    /**
     * Cetak sertifikat coach/official (tamu) — cukup nama + kontingen.
     */
    public function officialPrint(Request $request): Response
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kontingen_id' => ['required', 'integer', 'exists:kontingens,id'],
            'jabatan' => ['required', 'string', 'max:60'],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'kontingen_id.required' => 'Kontingen wajib dipilih.',
        ]);

        $official = new Official([
            'nama' => $data['nama'],
            'jabatan' => $data['jabatan'],
            'kontingen_id' => $data['kontingen_id'],
        ]);

        $official->setRelation('kontingen', Kontingen::findOrFail($data['kontingen_id']));

        // Tamu selalu mencetak dengan background.
        return $this->layanan->sertifikatOfficial($official, null, true);
    }
}
