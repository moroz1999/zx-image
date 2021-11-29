<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

//от Black Cat / Era
//
//Формат Profi GRF:+0 слово DW HSIZE горизонтальный размер картинки в точках
//+2 слово DW VSIZE вертикальный размер в строках растра
//+4 байт DB BPP бит на точку или точек в байте (в зависимости от AMOD)
//+5 байт DB AMOD 1 - цвет на каждую точку,
//0 - байт аттрибутов на байт точек
//+6 слово DW BPS длина образа одной строки растра в байтах
//+8 байт DB HLEN длина заголовка в записях по 128 байт (и 0, и 1 соответствует 128 байт)
//+9 байт DB 0 признак стандартного формата ( если формат будет изменяться, изменится и этот байт )
//+10 118 х DB 0 резерв
//или палитра (при +9=19($13)). 16 байт по 1 байту на цвет в формате GGGRRRBB
//
//
//BPP AMOD режим хранения информации
//--- ---- -----------------------------------------
//8 0 PROFI-mono
//4 0 PROFI-color (байты точек и аттрибутов чередуются, точки раньше аттрибутов)
//2 1 CGA (4 цвета, байт описывает 4 точки)
//4 1 EGA (16 цветов, байт описывает 2 точки)
//5 1 VGA (32 цвета, байт описывает 1 точку)
//8 1 VGA (256 цветов, байт описывает 1 точку)
//
//Причем я встречал вроде картинки только PROFI-color (байты точек и аттрибутов чередуются, точки раньше аттрибутов) и Profi-Mono

class Grf extends Standard
{
    private $bpp;
    private $amod;
    private $bps;
    private $hlen;
    private $format;

    protected function loadBits(): ?array
    {
        $pixelsArray = [];
        $attributesArray = [];
        if ($this->makeHandle()) {
            $this->width = $this->readWord();
            $this->height = $this->readWord();
            $this->bpp = $this->readByte();
            $this->amod = $this->readByte();
            $this->bps = $this->readWord();
            $this->hlen = $this->readByte();
            $this->format = $this->readByte();
            if ($this->format === 19) {
                $paletteArray = $this->read8BitStrings(16);
                $this->read8BitStrings(102);
            } else {
                $this->read8BitStrings(118);
            }
            $length = $this->width * $this->height / $this->bpp;
            do {
                $pixelsArray[] = $this->read8BitString();
                $attributesArray[] = $this->read8BitString();
            } while ($length = $length - 2);

            $resultBits = [
                'pixelsArray' => $pixelsArray,
                'paletteArray' => $paletteArray,
                'attributesArray' => $attributesArray,
            ];
            return $resultBits;
        }
        return null;
    }

    protected function parseScreen($data): array
    {
        $parsedData = [];
//        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);

        $x = 0;
        $y = 0;
        $pixelsData = [];
        foreach ($data['pixelsArray'] as $key => $bits) {
            $attrBits = $data['attributesArray'][$key];
            $ink = bindec(substr($attrBits, 1, 1) . substr($attrBits, 5, 3));
            $paper = bindec(substr($attrBits, 0, 1) . substr($attrBits, 2, 3));
            for ($number = 0; $number < 8; $number++) {
                $pixelsData[$y][$x] = bindec(substr($bits, $number, 1)) ? $ink : $paper;
                $x++;
            }
            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        $parsedData['pixelsData'] = $pixelsData;

        foreach ($data['paletteArray'] as $clutItem) {
            $greenChannel = bindec(substr($clutItem, 0, 3)) * 36;
            $redChannel = bindec(substr($clutItem, 3, 3)) * 36;
            $blueChannel = bindec(substr($clutItem, 6, 2)) * 85;
            $parsedData['colorsData'][] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }

        return $parsedData;
    }

    protected function exportData(array $parsedData, bool $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => $pixel) {
                $color = $parsedData['colorsData'][$pixel];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->resizeAspect($image);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    /**
     * @param resource $srcImage
     * @return resource
     */
    protected function resizeAspect($srcImage)
    {
        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);
        imagegammacorrect($srcImage, 2.2, 1.0);

        $dstWidth = $srcWidth;
        $dstHeight = (int)($srcHeight * 1.7);

        $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        imagegammacorrect($dstImage, 1.0, 2.2);

        return $dstImage;
    }

}
