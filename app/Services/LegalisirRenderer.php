<?php

namespace App\Services;

use GdImage;
use Illuminate\Support\Facades\File;

/**
 * Menyiapkan HTML sertifikat untuk cetak legalisir hitam putih.
 *
 * DOMPDF tidak mendukung CSS `filter: grayscale()`, sedangkan ekstensi
 * Imagick/Ghostscript tidak tersedia di server. Karena itu semua gambar lokal
 * (tag <img> dan CSS url(...)) dikonversi lebih dulu menjadi file grayscale
 * memakai GD, lalu path-nya diganti di HTML. Teks/border dipaksa hitam dan
 * latar dipaksa putih melalui CSS.
 */
class LegalisirRenderer
{
    private const CACHE_DIR = 'app/legalisir';

    /**
     * CSS yang memaksa dokumen menjadi hitam putih.
     */
    public static function cssGrayscale(): string
    {
        return '.legalisir-grayscale, .legalisir-grayscale * {'
            .' color:#000 !important;'
            .' border-color:#000 !important;'
            .' background-color:transparent !important;'
            .' text-shadow:none !important;'
            .' box-shadow:none !important;'
            .'}'
            .'.legalisir-grayscale { background-color:#fff !important; }'
            .'.legalisir-grayscale img { background-color:transparent !important; }';
    }

    /**
     * Ganti seluruh gambar lokal di HTML dengan versi grayscale.
     *
     * Tag <img> dengan atribut `data-stempel-legalisir` dilewati agar warna
     * stempel tetap sesuai unggahan admin.
     */
    public static function grayscaleHtml(string $html): string
    {
        $html = preg_replace_callback('/<img\b[^>]*>/i', function (array $match): string {
            $tag = $match[0];

            if (stripos($tag, 'data-stempel-legalisir') !== false) {
                return $tag;
            }

            return preg_replace_callback(
                '/\bsrc\s*=\s*(["\'])(.*?)\1/i',
                fn (array $src): string => self::gantiSrc($src),
                $tag,
            ) ?? $tag;
        }, $html) ?? $html;

        return preg_replace_callback(
            '/url\(\s*(["\']?)([^"\')]+)\1\s*\)/i',
            fn (array $url): string => self::gantiUrl($url),
            $html,
        ) ?? $html;
    }

    /**
     * Konversi satu sumber gambar lokal menjadi file grayscale.
     *
     * @return string|null Path absolut file grayscale, atau null bila bukan
     *                     gambar lokal / gagal diproses.
     */
    public static function grayscalePath(string $src): ?string
    {
        $src = trim(html_entity_decode($src));

        if ($src === '' || str_starts_with($src, '#') || preg_match('~^(https?:)?//~i', $src)) {
            return null;
        }

        if (preg_match('~^data:image/[^;]+;base64,(.+)$~i', $src, $data)) {
            return self::grayscaleData(base64_decode($data[1], true));
        }

        $path = self::resolvePath($src);

        if ($path === null) {
            return null;
        }

        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        $key = sha1($path.'|'.filemtime($path).'|'.$info[2]);
        $output = storage_path(self::CACHE_DIR.'/'.$key.'.png');

        if (is_file($output)) {
            return self::normalize($output);
        }

        $image = self::load($path, $info[2]);

        if (! $image instanceof GdImage) {
            return null;
        }

        File::ensureDirectoryExists(dirname($output));
        self::write($image, $output);
        imagedestroy($image);

        return is_file($output) ? self::normalize($output) : null;
    }

    /**
     * @param  array<int, string>  $match
     */
    private static function gantiSrc(array $match): string
    {
        $path = self::grayscalePath($match[2]);

        return $path === null ? $match[0] : 'src="'.e($path).'"';
    }

    /**
     * @param  array<int, string>  $match
     */
    private static function gantiUrl(array $match): string
    {
        $path = self::grayscalePath($match[2]);

        return $path === null ? $match[0] : 'url("'.e($path).'")';
    }

    /**
     * Konversi data URI base64 menjadi file grayscale (bukan data URI baru)
     * agar HTML hasil bulk tidak membengkak.
     */
    private static function grayscaleData(string|false $bytes): ?string
    {
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $key = sha1($bytes);
        $output = storage_path(self::CACHE_DIR.'/data-'.$key.'.png');

        if (is_file($output)) {
            return self::normalize($output);
        }

        $image = @imagecreatefromstring($bytes);

        if (! $image instanceof GdImage) {
            return null;
        }

        File::ensureDirectoryExists(dirname($output));
        self::write($image, $output, 'png');
        imagedestroy($image);

        return is_file($output) ? self::normalize($output) : null;
    }

    private static function resolvePath(string $src): ?string
    {
        $src = str_replace('\\', '/', $src);

        if (str_starts_with($src, 'file://')) {
            $src = substr($src, 7);
        }

        $candidates = [];

        if (preg_match('~^[A-Za-z]:/~', $src) || str_starts_with($src, '/')) {
            $candidates[] = $src;
        } else {
            $candidates[] = public_path($src);
            $candidates[] = base_path($src);
            $candidates[] = $src;
        }

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);

            if ($real !== false && is_file($real)) {
                return $real;
            }
        }

        return null;
    }

    private static function load(string $path, int $type): ?GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if (! $image instanceof GdImage) {
            return null;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagefilter($image, IMG_FILTER_GRAYSCALE);

        return $image;
    }

    private static function write(GdImage $image, string $output, string $extension = 'png'): void
    {
        if ($extension === 'jpg') {
            imagejpeg($image, $output, 88);

            return;
        }

        imagesavealpha($image, true);
        imagepng($image, $output);
    }

    private static function normalize(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
