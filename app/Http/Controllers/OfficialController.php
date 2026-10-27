<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfficialRequest;
use App\Models\Kontingen;
use App\Models\Official;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OfficialController extends Controller
{
    public function index(): View
    {
        return view('official.index', [
            'officials' => Official::with('kontingen')->orderBy('kontingen_id')->orderBy('nama')->paginate(25),
            'kontingens' => Kontingen::orderBy('nama')->get(),
            'jabatanOptions' => Official::jabatanOptions(),
        ]);
    }

    public function store(OfficialRequest $request): RedirectResponse
    {
        Official::create($request->validated());

        return redirect()
            ->route('official.index')
            ->with('success', 'Official berhasil ditambahkan. Gunakan tombol Cetak untuk membuat sertifikatnya.');
    }

    public function destroy(Official $official): RedirectResponse
    {
        $nama = $official->nama;
        $official->delete();

        return redirect()
            ->route('official.index')
            ->with('success', "Official \"{$nama}\" berhasil dihapus.");
    }
}
