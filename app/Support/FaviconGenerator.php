<?php

namespace App\Support;

/**
 * Builds the static fallback icons from the favicon uploaded in Settings:
 *   public/favicon.ico          (16/32/48 px — what browsers and Google request by default)
 *   public/apple-touch-icon.png (180 px — iOS home screen)
 * Pages link the uploaded favicon directly; these files cover everything else
 * (plain error pages, crawlers, bookmarks).
 */
class FaviconGenerator
{
    /** @param string $source public URL (/storage/…) or absolute path of the uploaded favicon */
    public function generate(string $source): bool
    {
        $path = $this->resolve($source);
        if (! $path || ! function_exists('imagecreatefromstring')) {
            return false;
        }
        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (! $src) {
            return false;
        }

        $entries = [];
        foreach ([16, 32, 48] as $size) {
            $entries[$size] = $this->png($src, $size);
        }
        $apple = $this->png($src, 180);
        imagedestroy($src);

        file_put_contents(public_path('favicon.ico'), $this->ico($entries), LOCK_EX);
        file_put_contents(public_path('apple-touch-icon.png'), $apple, LOCK_EX);

        return true;
    }

    private function resolve(string $source): ?string
    {
        if (is_file($source)) {
            return $source;
        }
        $rel  = ltrim((string) parse_url($source, PHP_URL_PATH), '/');
        $path = public_path($rel);

        return $rel !== '' && is_file($path) ? $path : null;
    }

    /** Square PNG of the given size (image is centred and letterboxed if not square). */
    private function png(\GdImage $src, int $size): string
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min($size / $w, $size / $h);
        $dw = max(1, (int) round($w * $scale));
        $dh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, (int) (($size - $dw) / 2), (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $w, $h);

        ob_start();
        imagepng($dst, null, 9);
        imagedestroy($dst);

        return (string) ob_get_clean();
    }

    /** Pack PNG images into an .ico container. @param array<int,string> $pngs size => png bytes */
    private function ico(array $pngs): string
    {
        $out    = pack('vvv', 0, 1, count($pngs));
        $offset = 6 + 16 * count($pngs);
        $data   = '';
        foreach ($pngs as $size => $png) {
            $out    .= pack('CCCCvvVV', $size >= 256 ? 0 : $size, $size >= 256 ? 0 : $size, 0, 0, 1, 32, strlen($png), $offset);
            $offset += strlen($png);
            $data   .= $png;
        }

        return $out . $data;
    }
}
