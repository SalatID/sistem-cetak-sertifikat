<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengaturanRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    /**
     * Key dokumen yang berupa teks (bukan file).
     */
    private const TEKS_KEYS = [
        'nama_kejuaraan',
        'singkatan_kejuaraan',
        'penyelenggara',
        'tempat_tanggal',
        'teks_sertifikat',
        'teks_sertifikat_official',
        'nama_ttd',
        'jabatan_ttd',
        'kota_ttd',
        'varian_pemasalan',
    ];

    /**
     * Key dokumen yang berupa berkas gambar.
     */
    private const GAMBAR_KEYS = [
        'logo_kiri',
        'logo_kanan',
        'background_sertifikat',
    ];

    public function index(): View
    {
        $values = [];

        foreach (array_merge(self::TEKS_KEYS, self::GAMBAR_KEYS) as $key) {
            $values[$key] = Setting::getValue($key);
        }

        return view('pengaturan.index', [
            'values' => $values,
            'urlGambar' => fn (?string $value): ?string => Setting::urlGambar($value),
        ]);
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(array_merge(self::GAMBAR_KEYS, ['password']));

        foreach ($data as $key => $value) {
            Setting::setValue($key, $value === '' ? null : $value);
        }

        foreach (self::GAMBAR_KEYS as $key) {
            if (! $request->hasFile($key)) {
                continue;
            }

            $path = $request->file($key)->store('dokumen', 'public');

            Setting::setValue($key, $path);
        }

        return redirect()
            ->route('pengaturan.index')
            ->with('success', 'Pengaturan dokumen berhasil disimpan.');
    }
}
