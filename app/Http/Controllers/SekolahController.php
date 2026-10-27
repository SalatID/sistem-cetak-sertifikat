<?php

namespace App\Http\Controllers;

use App\Models\Blangko;
use App\Models\Sekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Daftar sekolah & pencetakan sertifikat ucapan terima kasih (khusus admin).
 */
class SekolahController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->string('q')->toString());

        $sekolahs = Sekolah::query()
            ->when($q !== '', fn ($query) => $query->where('nama', 'like', '%'.$q.'%'))
            ->orderByRaw('terakhir_dicetak_at IS NULL')
            ->orderByDesc('terakhir_dicetak_at')
            ->orderBy('nama')
            ->paginate(25)
            ->withQueryString();

        return view('sekolah.index', [
            'sekolahs' => $sekolahs,
            'q' => $q,
            'blangkoSekolah' => Blangko::aktif(Blangko::JENIS_SEKOLAH),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:150',
                Rule::unique('sekolahs', 'nama'),
            ],
        ], [
            'nama.required' => 'Nama sekolah wajib diisi.',
            'nama.unique' => 'Nama sekolah sudah ada dalam daftar.',
        ]);

        $sekolah = Sekolah::create($data);

        return redirect()
            ->route('sekolah.index')
            ->with('success', "Sekolah \"{$sekolah->nama}\" ditambahkan.");
    }
}
