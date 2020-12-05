<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Filter\Filter;

abstract class Plugin implements Configurable
{
    /**
     * @var \ZxImage\Converter|null
     */
    protected $converter;
    /*
     * @var resource $handle
     */
    protected $handle;
    /**
     * @var int|null
     */
    protected $strictFileSize;
    /**
     * @var mixed[]
     */
    protected $colors = [];
    /**
     * @var mixed[]
     */
    protected $gigaColors = [];
    /**
     * @var string|null
     */
    protected $sourceFilePath;
    /**
     * @var string|null
     */
    protected $sourceFileContents;
    /**
     * @var string
     */
    protected $gigascreenMode = 'mix';
    /**
     * @var mixed[]
     */
    protected $palette;
    /**
     * @var int|null
     */
    protected $border = null;
    /**
     * @var float
     */
    protected $zoom = 1;
    /**
     * @var string|null
     */
    protected $resultMime = null;

    /**
     * @var mixed[]
     */
    protected $preFilters = [];
    /**
     * @var mixed[]
     */
    protected $postFilters = [];

    /**
     * @var int
     */
    protected $width = 256;
    /**
     * @var int
     */
    protected $height = 192;

    /**
     * @var int
     */
    protected $attributeWidth = 8;
    /**
     * @var int
     */
    protected $attributeHeight = 8;
    /**
     * @var int
     */
    protected $borderWidth = 32;
    /**
     * @var int
     */
    protected $borderHeight = 24;
    /**
     * @var int
     */
    protected $rotation;
    /**
     * @var string
     */
    protected $basePath;

    public function __construct(
        string $sourceFilePath = null,
        string $sourceFileContents = null,
        Converter $converter = null
    ) {
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
    }

    /**
     * @return void
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param Filter[] $filters
     * @return void
     */
    public function setPreFilters(array $filters)
    {
        $this->preFilters = $filters;
    }

    /**
     * @param Filter[] $filters
     * @return void
     */
    public function setPostFilters(array $filters)
    {
        $this->postFilters = $filters;
    }

    /**
     * @return void
     */
    public function setBorder(int $border = null)
    {
        $this->border = $border;
    }

    /**
     * @return void
     */
    public function setZoom(float $zoom)
    {
        $this->zoom = $zoom;
    }

    /**
     * @return void
     */
    public function setRotation(int $rotation)
    {
        $this->rotation = $rotation;
    }

    /**
     * @return void
     */
    public function setGigascreenMode(string $mode)
    {
        if ($mode == 'flicker' || $mode == 'interlace2' || $mode == 'interlace1') {
            $this->gigascreenMode = $mode;
        }
    }

    /**
     * @return void
     */
    public function setPalette(string $palette)
    {
        $this->parsePalette($palette);
        $this->generateColors();
        $this->generateGigaColors();
    }

    protected function parsePalette($palette)
    {
        $paletteData = explode(':', $palette);
        $baseColors = explode(',', $paletteData[0]);
        $correctionColors = explode(';', $paletteData[1]);
        $redData = explode(',', $correctionColors[0]);
        $greenData = explode(',', $correctionColors[1]);
        $blueData = explode(',', $correctionColors[2]);

        $result = [];

        $result['ZZ'] = intval($baseColors[0], 16);
        $result['ZN'] = intval($baseColors[1], 16);
        $result['NN'] = intval($baseColors[2], 16);
        $result['NB'] = intval($baseColors[3], 16);
        $result['BB'] = intval($baseColors[4], 16);
        $result['ZB'] = intval($baseColors[5], 16);

        $result['R11'] = intval($redData[0], 16);
        $result['R12'] = intval($redData[1], 16);
        $result['R13'] = intval($redData[2], 16);

        $result['R21'] = intval($greenData[0], 16);
        $result['R22'] = intval($greenData[1], 16);
        $result['R23'] = intval($greenData[2], 16);

        $result['R31'] = intval($blueData[0], 16);
        $result['R32'] = intval($blueData[1], 16);
        $result['R33'] = intval($blueData[2], 16);

        $this->palette = $result;
    }

    protected function generateColors()
    {
        $colors = [
            '0000' => 0,
            '0001' => 0,
            '0010' => 0,
            '0011' => 0,
            '0100' => 0,
            '0101' => 0,
            '0110' => 0,
            '0111' => 0,
            '1000' => 0,
            '1001' => 0,
            '1010' => 0,
            '1011' => 0,
            '1100' => 0,
            '1101' => 0,
            '1110' => 0,
            '1111' => 0,
        ];
        $palette = $this->palette;

        foreach ($colors as $zxColor => &$RGB) {
            $zxColor = (string)$zxColor;
            $brightness = substr($zxColor, 0, 1);

            $zero = $palette['ZZ'];
            $one = $palette['NN'];
            if ($brightness == '1') {
                $one = $palette['BB'];
            }

            $r = (1 - substr($zxColor, 2, 1)) * $zero + intval(substr($zxColor, 2, 1)) * $one;
            $g = (1 - substr($zxColor, 1, 1)) * $zero + intval(substr($zxColor, 1, 1)) * $one;
            $b = (1 - substr($zxColor, 3, 1)) * $zero + intval(substr($zxColor, 3, 1)) * $one;

            $redChannel = (int)round(($r * $palette['R11'] + $g * $palette['R12'] + $b * $palette['R13']) / 0xFF);
            $greenChannel = (int)round(($r * $palette['R21'] + $g * $palette['R22'] + $b * $palette['R23']) / 0xFF);
            $blueChannel = (int)round(($r * $palette['R31'] + $g * $palette['R32'] + $b * $palette['R33']) / 0xFF);

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }

        $this->colors = $colors;
    }

    protected function generateGigaColors()
    {
        $colors = [];
        $colors[] = '0000';
        $colors[] = '0001';
        $colors[] = '0010';
        $colors[] = '0011';
        $colors[] = '0100';
        $colors[] = '0101';
        $colors[] = '0110';
        $colors[] = '0111';
        $colors[] = '1000';
        $colors[] = '1001';
        $colors[] = '1010';
        $colors[] = '1011';
        $colors[] = '1100';
        $colors[] = '1101';
        $colors[] = '1110';
        $colors[] = '1111';

        $palette = $this->palette;
        $gigaColors = [];
        foreach ($colors as &$zxColor1) {
            foreach ($colors as &$zxColor2) {
                $gigaColors[$zxColor1 . $zxColor2] = 0;
            }
        }

        $cache = [];
        $cache['00'] = 'Z';
        $cache['01'] = 'N';
        $cache['10'] = 'Z';
        $cache['11'] = 'B';

        $palette['BN'] = $palette['NB'];
        $palette['BZ'] = $palette['ZB'];
        $palette['NZ'] = $palette['ZN'];

        foreach ($gigaColors as $zxColor => &$RGB) {
            $zxColor = (string)$zxColor;
            $brightness1 = substr($zxColor, 0, 1);
            $brightness2 = substr($zxColor, 4, 1);

            $r = $palette[$cache[$brightness1 . substr($zxColor, 2, 1)] . $cache[$brightness2 . substr(
                $zxColor,
                6,
                1
            )]];
            $g = $palette[$cache[$brightness1 . substr($zxColor, 1, 1)] . $cache[$brightness2 . substr(
                $zxColor,
                5,
                1
            )]];
            $b = $palette[$cache[$brightness1 . substr($zxColor, 3, 1)] . $cache[$brightness2 . substr(
                $zxColor,
                7,
                1
            )]];

            $redChannel = (int)round(($r * $palette['R11'] + $g * $palette['R12'] + $b * $palette['R13']) / 0xFF);
            $greenChannel = (int)round(($r * $palette['R21'] + $g * $palette['R22'] + $b * $palette['R23']) / 0xFF);
            $blueChannel = (int)round(($r * $palette['R31'] + $g * $palette['R32'] + $b * $palette['R33']) / 0xFF);

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }

        $this->gigaColors = $gigaColors;
    }

    /**
     * @return string|null
     */
    public function convert()
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            $image = $this->exportData($parsedData, false);
            $result = $this->makePngFromGd($image);
        }
        return $result;
    }

    /**
     * @return mixed[]|null
     */
    abstract protected function loadBits();

    abstract protected function parseScreen($data): array;

    abstract protected function exportData(array $parsedData, bool $flashedImage = false);

    /**
     * @param resource $image
     * @return string
     */
    protected function makePngFromGd($image): string
    {
        $this->resultMime = 'image/png';
        ob_start();
        imagepng($image);
        $binary = ob_get_contents();
        ob_end_clean();
        return $binary;
    }

    /**
     * @return mixed
     */
    public function getResultMime()
    {
        return $this->resultMime;
    }

    protected function makeHandle(): bool
    {
        if (is_file($this->sourceFilePath)) {
            if (!isset($this->strictFileSize)) {
                $this->strictFileSize = filesize($this->sourceFilePath);
            }
            if ($this->strictFileSize == filesize($this->sourceFilePath)) {
                $this->handle = fopen($this->sourceFilePath, "rb");
                return true;
            }
        } elseif ($this->sourceFileContents) {
            if (!isset($this->strictFileSize)) {
                $this->strictFileSize = strlen($this->sourceFileContents);
            }
            $this->handle = fopen('php://memory', 'w+');
            fwrite($this->handle, $this->sourceFileContents);
            rewind($this->handle);
            return true;
        }
        return false;
    }

    protected function read8BitStrings(int $length = 1): array
    {
        $strings = [];
        while ($length) {
            if (($byte = $this->readByte()) !== null) {
                $strings[] = str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
            }
            $length--;
        }

        return $strings;
    }

    /**
     * @return int|null
     */
    protected function readByte()
    {
        $read = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        } else {
            return ord($read);
        }
    }

    protected function read16BitStrings(int $length = 1, $bigEndian = true): array
    {
        $strings = [];
        while ($length) {
            if (($string = $this->read16BitString($bigEndian)) !== null) {
                $strings[] = $string;
            }
            $length--;
        }

        return $strings;
    }

    /**
     * @return string|null
     */
    protected function read16BitString(bool $bigEndian = true)
    {
        if ($b1 = $this->read8BitString()) {
            if ($b2 = $this->read8BitString()) {
                if (!$bigEndian) {
                    return $b2 . $b1;
                } else {
                    return $b1 . $b2;
                }
            }
        }
        return null;
    }

    /**
     * @return string|null
     */
    protected function read8BitString()
    {
        if (($byte = $this->readByte()) !== null) {
            return str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }
        return null;
    }

    /**
     * @return string|null
     */
    protected function readChar()
    {
        $result = null;
        if (($bits = $this->readByte()) || $bits === 0) {
            $result = chr($bits);
        }
        return $result;
    }

    /**
     * @return string|null
     */
    protected function readString(int $length)
    {
        $result = fread($this->handle, $length);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }
        return $result;
    }

    protected function readBytes(int $length): array
    {
        $result = [];
        while ($length--) {
            $result[] = $this->readByte();
        }
        return $result;
    }

    protected function readWords($length): array
    {
        $result = [];
        while ($length > 0) {
            $result[] = $this->readWord();
            $length--;
        }
        return $result;
    }

    /**
     * @return int|null
     */
    protected function readWord()
    {
        $b1 = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }
        $b2 = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }
        return ord($b2) * 256 + ord($b1);
    }

    /**
     * @param resource $srcImage
     * @return resource
     */
    protected function resizeImage($srcImage)
    {
        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);
        imagegammacorrect($srcImage, 2.2, 1);

        $dstWidth = $srcWidth;
        $dstHeight = $srcHeight;
        if ($this->zoom == 0.25) {
            $dstWidth = $srcWidth / 4;
            $dstHeight = $srcHeight / 4;
        } elseif ($this->zoom == 0.5) {
            $dstWidth = $srcWidth / 2;
            $dstHeight = $srcHeight / 2;
        } elseif ($this->zoom == 2) {
            $dstWidth = $srcWidth * 2;
            $dstHeight = $srcHeight * 2;
        } elseif ($this->zoom == 3) {
            $dstWidth = $srcWidth * 3;
            $dstHeight = $srcHeight * 3;
        }
        $this->applyPreFilters($srcImage);

        if ($this->zoom == 1) {
            $dstImage = $srcImage;
        } else {
            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);

            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        }
        $this->applyPostFilters($srcImage, $dstImage);

        imagegammacorrect($dstImage, 1, 2.2);

        return $dstImage;
    }

    /**
     * @param resource $srcImage
     * @return void
     */
    protected function applyPreFilters($srcImage)
    {
        foreach ($this->preFilters as $filterType) {
            $filterType = ucfirst($filterType);
            $className = '\\ZxImage\\Filter\\' . ucfirst($filterType);

            if (class_exists($className)) {
                /**
                 * @var Filter $filter
                 */
                $filter = new $className;
                $srcImage = $filter->apply($srcImage);
            }
        }
    }

    /**
     * @param resource $srcImage
     * @param resource $dstImage
     * @return void
     */
    protected function applyPostFilters($srcImage, $dstImage = null)
    {
        foreach ($this->postFilters as $filterType) {
            $filterType = ucfirst($filterType);
            $className = '\\ZxImage\\Filter\\' . ucfirst($filterType);

            if (class_exists($className)) {
                /**
                 * @var Filter $filter
                 */
                $filter = new $className;
                $dstImage = $filter->apply($dstImage, $srcImage);
            }
        }
    }

    /**
     * @param resource $centerImage
     * @param array|null $parsedData1
     * @param array|null $parsedData2
     * @param bool $merged
     * @return resource
     */
    protected function drawBorder(
        $centerImage,
        $parsedData1 = null,
        $parsedData2 = null,
        bool $merged = false
    ) {
        if (is_numeric($this->border)) {
            $resultImage = imagecreatetruecolor(
                $this->width + $this->borderWidth * 2,
                $this->height + $this->borderHeight * 2
            );
            $code = sprintf('%04.0f', decbin($this->border));
            $color = $this->colors[$code];
            imagefill($resultImage, 0, 0, $color);
            imagecopy(
                $resultImage,
                $centerImage,
                $this->borderWidth,
                $this->borderHeight,
                0,
                0,
                $this->width,
                $this->height
            );
        } else {
            $resultImage = $centerImage;
        }
        return $resultImage;
    }

    /**
     * @param resource $image
     * @return resource
     */
    protected function checkRotation($image)
    {
        $result = false;
        if ($this->rotation > 0) {
            $width = imagesx($image);
            $height = imagesy($image);
            switch ($this->rotation) {
                case 90:
                    $result = imagecreatetruecolor($height, $width);
                    break;
                case 180:
                    $result = imagecreatetruecolor($width, $height);
                    break;
                case 270:
                    $result = imagecreatetruecolor($height, $width);
                    break;
            }
            if ($result) {
                for ($i = 0; $i < $width; $i++) {
                    for ($j = 0; $j < $height; $j++) {
                        $reference = imagecolorat($image, $i, $j);
                        switch ($this->rotation) {
                            case 90:
                                if (!imagesetpixel($result, ($height - 1) - $j, $i, $reference)) {
                                    return null;
                                }
                                break;
                            case 180:
                                if (!imagesetpixel($result, $width - $i, ($height - 1) - $j, $reference)) {
                                    return null;
                                }
                                break;
                            case 270:
                                if (!imagesetpixel($result, $j, $width - $i, $reference)) {
                                    return null;
                                }
                                break;
                        }
                    }
                }
            } else {
                $result = imagerotate($image, $this->rotation, 0);
            }
        } else {
            $result = $image;
        }
        return $result;
    }

    /**
     * @param resource $image
     * @return string
     */
    protected function makeGifFromGd($image): string
    {
        $this->resultMime = 'image/gif';
        ob_start();
        imagegif($image);
        $binary = ob_get_contents();
        ob_end_clean();
        return $binary;
    }
}
