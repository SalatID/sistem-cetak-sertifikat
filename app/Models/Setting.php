<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['setting_key', 'setting_value'])]
class Setting extends Model
{
    protected $primaryKey = 'setting_key';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Ambil nilai setting dengan nilai cadangan bila key belum ada.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::query()->where('setting_key', $key)->value('setting_value') ?? $default;
    }

    /**
     * Simpan nilai setting (upsert).
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value],
        );
    }

    /**
     * Render template teks dokumen dengan menyisipkan nilai (placeholder {NAMA}, {JUARA_ROMAWI}, ...).
     *
     * @param  array<string, string>  $replacements
     */
    public static function renderTemplate(string $key, array $replacements, ?string $default = null): string
    {
        $template = static::getValue($key, $default ?? '');

        return strtr((string) $template, $replacements);
    }

    /**
     * Path absolut file gambar di storage (dipakai DOMPDF), atau null bila belum diunggah.
     */
    public static function pathGambar(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $path = public_path('storage/'.ltrim($value, '/'));

        return is_file($path) ? $path : null;
    }

    /**
     * URL publik gambar di storage (dipakai view Blade).
     */
    public static function urlGambar(?string $value): ?string
    {
        return blank($value) ? null : asset('storage/'.ltrim($value, '/'));
    }
}
