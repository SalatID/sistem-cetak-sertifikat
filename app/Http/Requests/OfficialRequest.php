<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfficialRequest extends FormRequest
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
            'jabatan' => ['required', 'string', 'max:60'],
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
            'nama.required' => 'Nama official wajib diisi.',
            'jabatan.required' => 'Jabatan wajib dipilih.',
        ];
    }
}
