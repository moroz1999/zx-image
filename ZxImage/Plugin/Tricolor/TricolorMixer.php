<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Tricolor;

use GdImage;

final readonly class TricolorMixer
{
    /**
     * @param GdImage[] $images
     */
    public function mix(array $images): GdImage
    {
        $first = reset($images);
        $width = imagesx($first);
        $height = imagesy($first);
        $result = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $overall = 0;
                foreach ($images as $image) {
                    $overall += imagecolorat($image, $x, $y);
                }
                imagesetpixel($result, $x, $y, $overall);
            }
        }
        return $result;
    }
}
