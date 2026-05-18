<?php

declare(strict_types=1);

namespace ZxImage\Service;

use GdImage;
use GifCreator\GifCreator;

readonly class ImageEncoder
{
    public function toPng(GdImage $image): string
    {
        ob_start();
        imagepng($image);
        return (string)ob_get_clean();
    }

    public function toGif(GdImage $image): string
    {
        ob_start();
        imagegif($image);
        return (string)ob_get_clean();
    }

    public function toPaletteGif(GdImage $image): string
    {
        $palettedImage = imagecreate(imagesx($image), imagesy($image));
        imagecopy($palettedImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagecolormatch($image, $palettedImage);
        return $this->toGif($palettedImage);
    }

    /**
     * @param string[] $frames
     * @param int[] $delays
     */
    public function toAnimatedGif(array $frames, array $delays): string
    {
        $gc = new GifCreator();
        $gc->create($frames, $delays, 0);
        return $gc->getGif();
    }
}
