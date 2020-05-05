<?php

namespace ZxImage;

if (!class_exists('\ZxImage\ConverterPluginConfigurable')) {
    include_once('ConverterPluginConfigurable.php');
}

abstract class ConverterPlugin implements ConverterPluginConfigurable
{
    protected $handle;
    protected $fileSize;
    protected $colors = [];
    protected $gigaColors = [];
    protected $sourceFilePath;
    protected $sourceFileContents;
    protected $gigascreenMode = 'mix';
    protected $palette = false;
    protected $border = false;
    protected $zoom = 1;
    protected $resultMime;

    protected $preFilters = [];
    protected $postFilters = [];

    protected $width = 256;
    protected $height = 192;

    protected $attributeWidth = 8;
    protected $attributeHeight = 8;
    protected $borderWidth = 32;
    protected $borderHeight = 24;
    protected $rotation;
    protected $basePath;

    public function __construct($sourceFilePath = null, $sourceFileContents = null)
    {
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
    }

    /**
     * @param mixed $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    protected function makeHandle()
    {
        if (file_exists($this->sourceFilePath)) {
            if (!$this->fileSize) {
                $this->fileSize = filesize($this->sourceFilePath);
            }
            if ($this->fileSize == filesize($this->sourceFilePath)) {
                $this->handle = fopen($this->sourceFilePath, "rb");
                return true;
            }
        } elseif ($this->sourceFileContents) {
            if (!$this->fileSize) {
                $this->fileSize = strlen($this->sourceFileContents);
            }
            $this->handle = fopen('php://memory', 'w+');
            fwrite($this->handle, $this->sourceFileContents);
            rewind($this->handle);
            return true;
        }
        return false;
    }

    /**
     * @param array $filters
     */
    public function setPreFilters($filters)
    {
        $this->preFilters = $filters;
    }

    /**
     * @param array $filters
     */
    public function setPostFilters($filters)
    {
        $this->postFilters = $filters;
    }

    public function setBorder($border)
    {
        $this->border = $border;
    }

    public function setZoom($zoom)
    {
        $this->zoom = $zoom;
    }

    public function setRotation($rotation)
    {
        $this->rotation = $rotation;
    }

    public function setGigascreenMode($mode)
    {
        if ($mode == 'flicker' || $mode == 'interlace2' || $mode == 'interlace1') {
            $this->gigascreenMode = $mode;
        }
    }

    public function setPalette($palette)
    {
        $this->parsePalette($palette);
        $this->generateColors();
        $this->generateGigaColors();
    }

    protected function read8BitString()
    {
        if (($byte = $this->readByte()) !== false) {
            return str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }
        return false;
    }

    protected function read8BitStrings($length = 1)
    {
        $strings = [];
        while ($length) {

            if (($byte = $this->readByte()) !== false) {
                $strings[] = str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
            }
            $length--;
        }

        return $strings;
    }


    protected function read16BitStrings($length = 1, $bigEndian = true)
    {
        $strings = [];
        while ($length) {
            if ($string = $this->read16BitString($bigEndian)) {
                $strings[] = $string;
            }
            $length--;
        }

        return $strings;
    }

    protected function read16BitString($bigEndian = true)
    {
        if ($b1 = $this->read8BitString()) {
            if ($b2 = $this->read8BitString()) {
                if (!$bigEndian){
                    return $b2 . $b1;
                } else {
                    return $b1 . $b2;
                }
            }
        }
        return false;
    }

    protected function readChar()
    {
        $result = false;
        if (($bits = $this->readByte()) || $bits === 0) {
            $result = chr($bits);
        }
        return $result;
    }

    protected function readByte()
    {
        $read = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return false;
        } else {
            return ord($read);
        }
    }

    protected function readString($length)
    {
        $result = fread($this->handle, $length);
        if (feof($this->handle)) {
            fclose($this->handle);
            return false;
        }
        return $result;
    }

    protected function readBytes($length)
    {
        $result = [];
        while ($length > 0) {
            $result[] = $this->readByte();
            $length--;
        }
        return $result;
    }

    protected function readWords($length)
    {
        $result = [];
        while ($length > 0) {
            $result[] = $this->readWord();
            $length--;
        }
        return $result;
    }

    protected function readWord()
    {
        $b1 = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return false;
        }
        $b2 = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return false;
        }
        return ord($b2) * 256 + ord($b1);

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

            $redChannel = round(($r * $palette['R11'] + $g * $palette['R12'] + $b * $palette['R13']) / 0xFF);
            $greenChannel = round(($r * $palette['R21'] + $g * $palette['R22'] + $b * $palette['R23']) / 0xFF);
            $blueChannel = round(($r * $palette['R31'] + $g * $palette['R32'] + $b * $palette['R33']) / 0xFF);

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }

        $this->gigaColors = $gigaColors;
    }

    protected function generateColors()
    {
        $colors = [];
        $colors['0000'] = 0;
        $colors['0001'] = 0;
        $colors['0010'] = 0;
        $colors['0011'] = 0;
        $colors['0100'] = 0;
        $colors['0101'] = 0;
        $colors['0110'] = 0;
        $colors['0111'] = 0;
        $colors['1000'] = 0;
        $colors['1001'] = 0;
        $colors['1010'] = 0;
        $colors['1011'] = 0;
        $colors['1100'] = 0;
        $colors['1101'] = 0;
        $colors['1110'] = 0;
        $colors['1111'] = 0;

        $palette = $this->palette;

        foreach ($colors as $zxColor => &$RGB) {
            $brightness = substr($zxColor, 0, 1);

            $zero = $palette['ZZ'];
            $one = $palette['NN'];
            if ($brightness == '1') {
                $one = $palette['BB'];
            }

            $r = (1 - substr($zxColor, 2, 1)) * $zero + intval(substr($zxColor, 2, 1)) * $one;
            $g = (1 - substr($zxColor, 1, 1)) * $zero + intval(substr($zxColor, 1, 1)) * $one;
            $b = (1 - substr($zxColor, 3, 1)) * $zero + intval(substr($zxColor, 3, 1)) * $one;

            $redChannel = round(($r * $palette['R11'] + $g * $palette['R12'] + $b * $palette['R13']) / 0xFF);
            $greenChannel = round(($r * $palette['R21'] + $g * $palette['R22'] + $b * $palette['R23']) / 0xFF);
            $blueChannel = round(($r * $palette['R31'] + $g * $palette['R32'] + $b * $palette['R33']) / 0xFF);

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }

        $this->colors = $colors;
    }

    protected function resizeImage($srcImage)
    {
        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);
        imagegammacorrect($srcImage, 2.2, 1.0);

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

        imagegammacorrect($dstImage, 1.0, 2.2);

        return $dstImage;
    }

    protected function applyPreFilters($srcImage)
    {
        if (!class_exists('\ZxImage\ConverterFilter')) {
            $path = $this->basePath . 'ConverterFilter.php';
            if (file_exists($path)) {
                include_once($path);
            }
        }
        foreach ($this->preFilters as $filterType) {
            $filterType = ucfirst($filterType);
            $fileName = 'Filter' . DIRECTORY_SEPARATOR . $filterType . '.php';
            $className = '\ZxImage\\' . $filterType;

            if (!class_exists($className)) {
                $path = $this->basePath . $fileName;
                if (file_exists($path)) {
                    include_once($path);
                }
            }
            if (class_exists($className)) {
                /**
                 * @var ConverterFilter
                 */
                $filter = new $className;
                $srcImage = $filter->apply($srcImage);
            }
        }
    }

    protected function applyPostFilters($srcImage, $dstImage = false)
    {
        if (!class_exists('\ZxImage\ConverterFilter')) {
            $path = $this->basePath . 'ConverterFilter.php';
            if (file_exists($path)) {
                include_once($path);
            }
        }
        foreach ($this->postFilters as $filterType) {
            $filterType = ucfirst($filterType);
            $fileName = 'Filter' . DIRECTORY_SEPARATOR . $filterType . '.php';
            $className = '\ZxImage\\' . $filterType;

            if (!class_exists($className)) {
                $path = $this->basePath . $fileName;
                if (file_exists($path)) {
                    include_once($path);
                }
            }
            if (class_exists($className)) {
                /**
                 * @var ConverterFilter
                 */
                $filter = new $className;
                $dstImage = $filter->apply($dstImage, $srcImage);
            }
        }
    }

    protected function drawBorder($centerImage, $parsedData1 = false, $parsedData2 = false, $merged = false)
    {
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
                                    return false;
                                }
                                break;
                            case 180:
                                if (!imagesetpixel($result, $width - $i, ($height - 1) - $j, $reference)) {
                                    return false;
                                }
                                break;
                            case 270:
                                if (!imagesetpixel($result, $j, $width - $i, $reference)) {
                                    return false;
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

    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            $image = $this->exportData($parsedData, false);
            $result = $this->makePngFromGd($image);
        }
        return $result;
    }

    protected function makePngFromGd($image)
    {
        $this->resultMime = 'image/png';
        ob_start();
        imagepng($image);
        $binary = ob_get_contents();
        ob_end_clean();
        return $binary;
    }

    protected function makeGifFromGd($image)
    {
        $this->resultMime = 'image/gif';
        ob_start();
        imagegif($image);
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

    abstract protected function loadBits();

    abstract protected function parseScreen($data);

    abstract protected function exportData($parsedData, $flashedImage = false);
}
