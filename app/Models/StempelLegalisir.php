<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'gambar', 'x_mm', 'y_mm', 'lebar_mm', 'tinggi_mm', 'aktif'])]
class StempelLegalisir extends Model
{
    protected $table = 'stempel_legalisirs';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'x_mm' => 'float',
            'y_mm' => 'float',
            'lebar_mm' => 'float',
            'tinggi_mm' => 'float',
            'aktif' => 'boolean',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Path absolut gambar stempel untuk DOMPDF, atau null bila file tidak ada.
     */
    public function pathGambar(): ?string
    {
        if (blank($this->gambar)) {
            return null;
        }

        $path = public_path('storage/'.ltrim($this->gambar, '/'));

        return is_file($path) ? $path : null;
    }

    /**
     * URL publik gambar stempel untuk preview browser.
     */
    public function urlGambar(): ?string
    {
        return blank($this->gambar) ? null : asset('storage/'.ltrim($this->gambar, '/'));
    }

    /**
     * Label posisi ringkas untuk daftar stempel.
     */
    public function labelPosisi(): string
    {
        return sprintf(
            'X %s mm · Y %s mm · %s × %s mm',
            $this->formatMm($this->x_mm),
            $this->formatMm($this->y_mm),
            $this->formatMm($this->lebar_mm),
            $this->formatMm($this->tinggi_mm),
        );
    }

    private function formatMm(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }
}
