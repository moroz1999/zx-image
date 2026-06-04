<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Tricolor;

use GdImage;
use RuntimeException;

final readonly class TricolorMixer
{
    /**
     * @param non-empty-list<GdImage> $images
     */
    public function mix(array $images): GdImage
    {
        $first = reset($images);
        $width = imagesx($first);
        $height = imagesy($first);
        $result = imagecreatetruecolor($width, $height);
        if ($result === false) {
            throw new RuntimeException('Unable to create GD image');
        }

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $overall = 0;
                foreach ($images as $image) {
                    $color = imagecolorat($image, $x, $y);
                    if ($color === false) {
                        throw new RuntimeException('Unable to read GD image pixel');
                    }
                    $overall += $color;
                }
                imagesetpixel($result, $x, $y, $overall);
            }
        }
        return $result;
    }
}
