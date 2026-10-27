<?php

namespace App\Http\Requests;

use App\Models\Peserta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PesertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'kontingen_id' => ['required', 'integer', 'exists:kontingens,id'],
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'kategori' => ['required', 'string', 'max:120'],
            'kelas' => ['nullable', 'string', 'max:120'],
            'kelompok_usia' => ['nullable', 'string', 'max:40', Rule::in(Peserta::pilihanKelompokUsia())],
            'juara' => ['nullable', 'string', 'max:100'],
            'no_sk' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kontingen_id.required' => 'Kontingen wajib dipilih.',
            'kontingen_id.exists' => 'Kontingen yang dipilih tidak ditemukan.',
            'nama.required' => 'Nama peserta wajib diisi.',
            'kategori.required' => 'Kategori wajib diisi.',
            'juara.max' => 'Keterangan juara maksimal 100 karakter.',
        ];
    }
}
