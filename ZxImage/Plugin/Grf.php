<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;

class Grf implements PluginInterface
{
    use PluginConfigTrait;

    private const int PROFI_COLOR_FORMAT = 19;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    public function convert(): ?string
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, null);
        if ($reader === null) {
            return null;
        }

        $this->width = $reader->readWord() ?? $this->width;
        $this->height = $reader->readWord() ?? $this->height;
        $bpp = $reader->readByte() ?? 4;
        $reader->readByte(); // amod
        $reader->readByte(); // bps lo
        $reader->readByte(); // bps hi
        $reader->readByte(); // hlen
        $format = $reader->readByte() ?? 0;

        $paletteBytes = [];
        if ($format === self::PROFI_COLOR_FORMAT) {
            $paletteBytes = $reader->readBytes(16);
            $reader->readBytes(102);
        } else {
            $reader->readBytes(118);
        }

        $pixelsArray = [];
        $attributesArray = [];
        $length = (int)($this->width * $this->height / $bpp);
        do {
            $pixelsArray[] = $reader->readByte();
            $attributesArray[] = $reader->readByte();
        } while ($length = $length - 2);

        $pixelsData = $this->parseGrfPixels($pixelsArray, $attributesArray);
        $colors = $this->parseGrfPalette($paletteBytes);

        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($pixelsData as $y => $row) {
            foreach ($row as $x => $pixel) {
                imagesetpixel($image, $x, $y, $colors[$pixel]);
            }
        }

        $image = $this->resizeAspect($image);

        $colorTable = $this->paletteService->buildColorTable($this->paletteString);
        $image = $this->imageProcessor->resize($image, $this->zoom, $this->preFilters, $this->postFilters);
        $image = $this->imageProcessor->rotate($image, $this->rotation);

        $this->resultMime = 'image/png';
        return $this->imageEncoder->toPng($image);
    }

    private function parseGrfPixels(array $pixelsArray, array $attributesArray): array
    {
        $x = 0;
        $y = 0;
        $pixelsData = [];

        foreach ($pixelsArray as $key => $pixelByte) {
            $attrByte = $attributesArray[$key];
            $ink = (($attrByte >> 3) & 0x08) | ($attrByte & 0x07);
            $paper = (($attrByte >> 4) & 0x08) | (($attrByte >> 3) & 0x07);
            for ($number = 0; $number < 8; $number++) {
                $pixelsData[$y][$x] = ($pixelByte & (0x80 >> $number)) ? $ink : $paper;
                $x++;
            }
            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;
    }

    private function parseGrfPalette(array $paletteBytes): array
    {
        $colors = [];
        foreach ($paletteBytes as $byte) {
            $green = (($byte >> 5) & 0x07) * 36;
            $red = (($byte >> 2) & 0x07) * 36;
            $blue = ($byte & 0x03) * 85;
            $colors[] = $red * 0x010000 + $green * 0x0100 + $blue;
        }
        return $colors;
    }

    private function resizeAspect(GdImage $srcImage): GdImage
    {
        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);
        imagegammacorrect($srcImage, 2.2, 1.0);

        $dstWidth = $srcWidth;
        $dstHeight = (int)($srcHeight * 1.6384);

        $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        imagecopyresized($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        imagegammacorrect($dstImage, 1.0, 2.2);

        return $dstImage;
    }
}
