<?php

namespace App\Http\Requests;

use App\Models\Blangko;
use App\Models\StempelLegalisir;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StempelLegalisirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Dimensi halaman aktif yang menjadi acuan posisi stempel.
     *
     * @return array{lebar: int, tinggi: int}
     */
    public static function dimensiHalaman(): array
    {
        $blangko = Blangko::aktif(Blangko::JENIS_SERTIFIKAT);

        return [
            'lebar' => (int) ($blangko?->lebar_mm ?? 297),
            'tinggi' => (int) ($blangko?->tinggi_mm ?? 210),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $stempel = $this->route('stempel');
        $id = $stempel instanceof StempelLegalisir ? $stempel->id : null;
        $page = self::dimensiHalaman();

        $unique = Rule::unique('stempel_legalisirs', 'nama');

        if ($id !== null) {
            $unique->ignore($id);
        }

        return [
            'nama' => ['required', 'string', 'max:150', $unique],
            'gambar' => [$id === null ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'x_mm' => ['required', 'numeric', 'min:0', 'max:'.$page['lebar']],
            'y_mm' => ['required', 'numeric', 'min:0', 'max:'.$page['tinggi']],
            'lebar_mm' => ['required', 'numeric', 'min:5', 'max:'.$page['lebar']],
            'tinggi_mm' => ['required', 'numeric', 'min:5', 'max:'.$page['tinggi']],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $page = self::dimensiHalaman();
            $x = (float) $this->input('x_mm', 0);
            $y = (float) $this->input('y_mm', 0);
            $lebar = (float) $this->input('lebar_mm', 0);
            $tinggi = (float) $this->input('tinggi_mm', 0);

            if ($x + $lebar > $page['lebar'] + 0.01) {
                $validator->errors()->add(
                    'lebar_mm',
                    'Posisi X + lebar stempel melewati lebar halaman ('.$page['lebar'].' mm).'
                );
            }

            if ($y + $tinggi > $page['tinggi'] + 0.01) {
                $validator->errors()->add(
                    'tinggi_mm',
                    'Posisi Y + tinggi stempel melewati tinggi halaman ('.$page['tinggi'].' mm).'
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama stempel wajib diisi.',
            'nama.unique' => 'Nama stempel sudah dipakai.',
            'gambar.required' => 'Gambar stempel wajib diunggah.',
            'gambar.image' => 'Stempel harus berupa gambar (jpg/png/webp).',
            'gambar.mimes' => 'Format stempel harus jpg, png, atau webp.',
            'gambar.max' => 'Ukuran gambar stempel maksimal 4 MB.',
            'x_mm.required' => 'Posisi X wajib diisi.',
            'y_mm.required' => 'Posisi Y wajib diisi.',
            'lebar_mm.required' => 'Lebar stempel wajib diisi.',
            'tinggi_mm.required' => 'Tinggi stempel wajib diisi.',
            'x_mm.numeric' => 'Posisi X harus berupa angka.',
            'y_mm.numeric' => 'Posisi Y harus berupa angka.',
            'lebar_mm.numeric' => 'Lebar stempel harus berupa angka.',
            'tinggi_mm.numeric' => 'Tinggi stempel harus berupa angka.',
            'x_mm.min' => 'Posisi X tidak boleh negatif.',
            'y_mm.min' => 'Posisi Y tidak boleh negatif.',
            'lebar_mm.min' => 'Lebar stempel minimal 5 mm.',
            'tinggi_mm.min' => 'Tinggi stempel minimal 5 mm.',
        ];
    }
}
