<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'nama_kejuaraan' => ['required', 'string', 'max:150'],
            'singkatan_kejuaraan' => ['nullable', 'string', 'max:50'],
            'penyelenggara' => ['nullable', 'string', 'max:150'],
            'tempat_tanggal' => ['nullable', 'string', 'max:150'],
            'nama_ttd' => ['nullable', 'string', 'max:150'],
            'jabatan_ttd' => ['nullable', 'string', 'max:100'],
            'kota_ttd' => ['nullable', 'string', 'max:100'],
            'teks_sertifikat' => ['nullable', 'string', 'max:1000'],
            'teks_sertifikat_official' => ['nullable', 'string', 'max:1000'],
            'logo_kiri' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logo_kanan' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'background_sertifikat' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_kejuaraan.required' => 'Nama kejuaraan wajib diisi.',
            'logo_kiri.image' => 'Logo kiri harus berupa gambar (jpg/png/webp).',
            'logo_kanan.image' => 'Logo kanan harus berupa gambar (jpg/png/webp).',
            'background_sertifikat.image' => 'Background sertifikat harus berupa gambar (jpg/png/webp).',
            'logo_kiri.max' => 'Ukuran logo kiri maksimal 2 MB.',
            'logo_kanan.max' => 'Ukuran logo kanan maksimal 2 MB.',
            'background_sertifikat.max' => 'Ukuran background maksimal 2 MB.',
        ];
    }
}
