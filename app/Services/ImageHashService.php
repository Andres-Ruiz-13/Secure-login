<?php

namespace App\Services;

class ImageHashService
{
    /**
     * Calcula el Average Hash (aHash) de 64 bits para una imagen en disco utilizando GD.
     * Retorna una cadena hexadecimal de 16 caracteres (o null si la imagen es inválida).
     */
    public static function generateHash(string $filePath): ?string
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return null;
        }

        // Si la extensión GD no está habilitada en el entorno PHP, proveer fallback consistente de 16 caracteres hex
        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            return substr(md5_file($filePath), 0, 16);
        }

        $imageInfo = @getimagesize($filePath);
        if (! $imageInfo) {
            return null;
        }

        $mime = $imageInfo['mime'];
        $srcImage = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($filePath),
            'image/png'  => @imagecreatefrompng($filePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : null,
            'image/gif'  => @imagecreatefromgif($filePath),
            default      => null,
        };

        if (! $srcImage) {
            return null;
        }

        // Redimensionar a 8x8 píxeles
        $dstImage = imagecreatetruecolor(8, 8);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, 8, 8, imagesx($srcImage), imagesy($srcImage));
        imagefilter($dstImage, IMG_FILTER_GRAYSCALE);

        // Extraer luminosidad de los 64 píxeles y calcular el promedio
        $pixels = [];
        $totalBrightness = 0;

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($dstImage, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                // Luminosidad estándar ITU-R BT.601
                $luminance = (int) (($r * 0.299) + ($g * 0.587) + ($b * 0.114));
                $pixels[] = $luminance;
                $totalBrightness += $luminance;
            }
        }

        $avgBrightness = $totalBrightness / 64;

        // Generar 64 bits: 1 si pixel >= promedio, 0 si menor
        $bitString = '';
        foreach ($pixels as $pixel) {
            $bitString .= ($pixel >= $avgBrightness) ? '1' : '0';
        }

        imagedestroy($dstImage);
        imagedestroy($srcImage);

        // Convertir los 64 bits a una cadena hexadecimal de 16 caracteres
        $hexHash = '';
        for ($i = 0; $i < 64; $i += 4) {
            $nibble = substr($bitString, $i, 4);
            $hexHash .= dechex(bindec($nibble));
        }

        return str_pad($hexHash, 16, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula la distancia de Hamming entre dos hashes hexadecimales (0 a 64 bits distintos).
     */
    public static function hammingDistance(string $hex1, string $hex2): int
    {
        $hex1 = str_pad(strtolower(trim($hex1)), 16, '0', STR_PAD_LEFT);
        $hex2 = str_pad(strtolower(trim($hex2)), 16, '0', STR_PAD_LEFT);

        $distance = 0;

        // Procesar en dos mitades de 32 bits (8 caracteres hex cada una)
        for ($i = 0; $i < 16; $i += 8) {
            $chunk1 = hexdec(substr($hex1, $i, 8));
            $chunk2 = hexdec(substr($hex2, $i, 8));
            $xor = $chunk1 ^ $chunk2;

            // Contar bits encendidos en el XOR
            $binary = decbin($xor);
            $distance += substr_count($binary, '1');
        }

        return $distance;
    }
}
