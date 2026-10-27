<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KontingenRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:150', 'unique:kontingens,nama,'.$this->route('kontingen')?->id],
            'kota' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kontingen wajib diisi.',
            'nama.unique' => 'Kontingen dengan nama tersebut sudah terdaftar.',
        ];
    }
}
