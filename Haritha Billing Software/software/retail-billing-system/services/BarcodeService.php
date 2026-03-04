<?php
/**
 * BarcodeService - PHP 5.6 compatible
 * Generates EAN-13 barcodes as SVG (no external library needed)
 */
class BarcodeService
{

    /**
     * Generate a unique 13-digit EAN-13 barcode number
     */
    public static function generateUniqueBarcode()
    {
        $base = str_pad(mt_rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);
        $check = self::ean13CheckDigit($base);
        return $base . $check;
    }

    /**
     * Calculate EAN-13 check digit
     */
    public static function ean13CheckDigit($digits12)
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $d = (int) $digits12[$i];
            $sum += ($i % 2 === 0) ? $d : ($d * 3);
        }
        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Generate simple SVG barcode
     */
    public static function getBarcodeSVG($barcode, $width = 200, $height = 70)
    {
        $code = str_pad($barcode, 13, '0', STR_PAD_LEFT);
        $barW = $width / 60;
        $x = 2;
        $bars = '';
        $heights = array(3, 2, 1, 2, 3, 1, 2, 1, 3, 2);

        for ($i = 0; $i < strlen($code); $i++) {
            $d = (int) $code[$i];
            $bw = $barW * ($heights[$d % 10]);
            if ($i % 2 === 0) {
                $bars .= '<rect x="' . round($x, 1) . '" y="2" width="' . round($bw, 1) . '" height="' . ($height - 16) . '" fill="#222"/>';
            }
            $x += $bw + ($barW * 0.3);
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '">';
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="white" rx="2"/>';
        $svg .= $bars;
        $svg .= '<text x="' . ($width / 2) . '" y="' . ($height - 3) . '" text-anchor="middle"';
        $svg .= ' font-family="Courier New,monospace" font-size="9" fill="#333">' . $code . '</text>';
        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Get as data URI for img src
     */
    public static function getBarcodeDataURI($barcode)
    {
        $svg = self::getBarcodeSVG($barcode);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Validate 13-digit barcode
     */
    public static function validate($barcode)
    {
        return (bool) preg_match('/^\d{13}$/', $barcode);
    }
}
