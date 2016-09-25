<?php
namespace ZxImage;

interface ConverterPluginConfigurable
{
    public function __construct($sourceFilePath, $resultFilePath);

    public function convert();

    public function setBorder($border);

    public function setPalette($palette);

    public function setSize($size);

    public function setRotation($rotation);

    public function setGigascreenMode($mode);
}

class Converter
{
    protected $hash = false;
    protected $colors = array();
    protected $gigascreenMode = 'mix';
    protected $cachePath;
    protected $sourceFilePath;
    protected $resultFilePath;
    protected $cacheDirMarkerPath;
    protected $cacheDeletionPeriod = 300; //start cache clearing every 5 minutes
    protected $cacheDeletionAmount = 1000; //delete not more than 1000 images at once
    protected $cacheExpirationLimit = false;
    protected $type = 'standard';
    protected $border = false;
    protected $size = '2';
    protected $rotation = '0';
    protected $cacheFileName;
    protected $cacheEnabled = true;

    /**
     * @param boolean $cacheEnabled
     */
    public function setCacheEnabled($cacheEnabled)
    {
        $this->cacheEnabled = $cacheEnabled;
    }

    protected $palette = '';
    protected $palette1 = '00,76,CD,E9,FF,9F:FF,00,00;00,FF,00;00,00,FF'; //pulsar
    protected $palette2 = '00,76,CD,E9,FF,9F:D0,00,00;00,E4,00;00,00,FF'; //orthodox
    protected $palette3 = '00,60,A0,E0,FF,A0:FF,00,00;00,FF,00;00,00,FF'; //alone
    protected $palette4 = '4F,A1,DD,F0,FF,BD:39,73,1D;3C,77,1E;46,8C,23'; //electroscale

    public function __construct()
    {
        $this->palette = $this->palette1;
        $this->cacheExpirationLimit = 60 * 60 * 24 * 30; //delete files older than 1 month
    }

    /**
     * @param mixed $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath;
        $this->cacheDirMarkerPath = $this->cachePath . '/_marker';
    }

    /**
     * @param bool|int $cacheExpirationLimit
     */
    public function setCacheExpirationLimit($cacheExpirationLimit)
    {
        $this->cacheExpirationLimit = $cacheExpirationLimit;
    }

    /**
     * @param int $cacheDeletionAmount
     */
    public function setCacheDeletionAmount($cacheDeletionAmount)
    {
        $this->cacheDeletionAmount = $cacheDeletionAmount;
    }

    /**
     * @param int $cacheDeletionPeriod
     */
    public function setCacheDeletionPeriod($cacheDeletionPeriod)
    {
        $this->cacheDeletionPeriod = $cacheDeletionPeriod;
    }

    public function setGigascreenMode($mode)
    {
        if ($mode == 'flicker' || $mode == 'interlace2' || $mode == 'interlace1' || $mode == 'mix') {
            $this->gigascreenMode = $mode;
        }
    }

    public function setRotation($rotation)
    {
        if (in_array($rotation, array(0, 90, 180, 270))) {
            $this->rotation = $rotation;
        }
    }

    public function setPalette($palette)
    {
        if ($palette == 'orthodox') {
            $this->palette = $this->palette2;
        } elseif ($palette == 'alone') {
            $this->palette = $this->palette3;
        } elseif ($palette == 'electroscale') {
            $this->palette = $this->palette4;
        } else {
            $this->palette = $this->palette1;
        }
    }

    public function setBorder($border)
    {
        if ($border >= 0 && $border < 8 || $border === false) {
            $this->border = $border;
        }
    }

    public function setSize($size)
    {
        if (is_numeric($size)) {
            $size = intval($size);
            if ($size >= 0 && $size <= 5) {
                $this->size = $size;
            }
        }
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setPath($path)
    {
        $this->sourceFilePath = $path;
    }

    public function getCacheFileName()
    {
        $parametersHash = $this->getHash();

        $this->cacheFileName = $this->cachePath . $parametersHash;
        return $this->cacheFileName;
    }

    public function executeProcess()
    {
        if (!$this->cacheEnabled) {
            $resultFilePath = $this->getCacheFileName();
            if (!file_exists($resultFilePath)) {
                $converter = false;
                if ($this->type == 'standard') {
                    $converter = new ConverterPlugin_standard($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'hidden') {
                    $converter = new ConverterPlugin_hidden($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'monochrome') {
                    $converter = new ConverterPlugin_monochrome($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'flash') {
                    $converter = new ConverterPlugin_flash($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'gigascreen') {
                    $converter = new ConverterPlugin_gigascreen($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'tricolor') {
                    $converter = new ConverterPlugin_tricolor($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'multiartist' || $this->type == 'mg1' || $this->type == 'mg2' || $this->type == 'mg4' || $this->type == 'mg8') {
                    $converter = new ConverterPlugin_multiartist($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'multicolor') {
                    $converter = new ConverterPlugin_multicolor($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'multicolor4') {
                    $converter = new ConverterPlugin_multicolor4($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'mc') {
                    $converter = new ConverterPlugin_mc($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'timex81') {
                    $converter = new ConverterPlugin_timex81($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'bsc') {
                    $converter = new ConverterPlugin_bsc($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'bmc4') {
                    $converter = new ConverterPlugin_bmc4($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'attributes') {
                    $converter = new ConverterPlugin_attributes($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'lowresgs') {
                    $converter = new ConverterPlugin_lowresgs($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'chr$') {
                    $converter = new ConverterPlugin_chrd($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'attributesm') {
                    $converter = new ConverterPlugin_attributesm($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'ulaplus') {
                    $converter = new ConverterPlugin_ulaplus($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'sam4') {
                    $converter = new ConverterPlugin_sam4($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'zxevo') {
                    $converter = new ConverterPlugin_zxevo($this->sourceFilePath, $resultFilePath);
                } elseif ($this->type == 'sxg') {
                    $converter = new ConverterPlugin_sxg($this->sourceFilePath, $resultFilePath);
                }
                if ($converter) {
                    $converter->setBorder($this->border);
                    $converter->setPalette($this->palette);
                    $converter->setSize($this->size);
                    $converter->setRotation($this->rotation);
                    $converter->setGigascreenMode($this->gigascreenMode);
                    $converter->convert();
                }
            }
        }
        if ($this->cacheEnabled){
            $this->checkCacheClearing();
        }
        return true;
    }

    public function getHash()
    {
        if (!$this->hash && is_file($this->sourceFilePath)) {
            $text = '';
            $text .= $this->sourceFilePath;
            $text .= filemtime($this->sourceFilePath);
            $text .= $this->type;
            if (in_array(
                $this->type,
                array(
                    'gigascreen',
                    'tricolor',
                    'multiartist',
                    'mg1',
                    'mg2',
                    'mg4',
                    'mg8',
                    'lowresgs',
                    'chr$'
                )
            )
            ) {
                if (($this->gigascreenMode == 'interlace1' || $this->gigascreenMode == 'interlace2') && ($this->size == '0' || $this->size == '1')) {
                    $text .= 'flicker';
                } else {
                    $text .= $this->gigascreenMode;
                }
            }
            $text .= $this->border;
            $text .= $this->palette;
            $text .= $this->size;
            if ($this->rotation > 0) {
                $text .= $this->rotation;
            }

            $this->hash = md5($text);
        }
        return $this->hash;
    }

    protected function checkCacheClearing()
    {
        if ($date = $this->getCacheLastClearedDate()) {
            $now = time();
            if ($now - $date >= $this->cacheDeletionPeriod) {
                touch($this->cacheDirMarkerPath);
                $this->clearOutdatedCache();
            }
        }
    }

    protected function clearOutdatedCache()
    {
        $c = 0;
        $now = time();
        if ($handler = opendir($this->cachePath)) {
            while (($fileName = readdir($handler)) !== false) {
                $filePath = $this->cachePath . $fileName;
                if (is_file($filePath)) {
                    if ($now - filectime($filePath) > $this->cacheExpirationLimit) {
                        $c++;
                        unlink($filePath);
                    }

                }
                if ($c >= $this->cacheDeletionAmount) {
                    break;
                }
            }
            closedir($handler);
        }
        return $c;
    }

    protected function getCacheLastClearedDate()
    {
        $date = false;

        if (!is_file($this->cacheDirMarkerPath)) {
            file_put_contents($this->cacheDirMarkerPath, ' ');
            return 1;
        }
        if (is_file($this->cacheDirMarkerPath)) {
            $date = filemtime($this->cacheDirMarkerPath);
        }
        return $date;
    }
}

abstract class ConverterPlugin implements ConverterPluginConfigurable
{
    protected $handle = null;
    protected $colors = array();
    protected $gigaColors = array();
    protected $sourceFilePath;
    protected $gigascreenMode = 'mix';
    protected $palette = false;
    protected $border = false;
    protected $size = '0';
    protected $resultFilePath = '';

    protected $width = 256;
    protected $height = 192;

    protected $attributeWidth = 8;
    protected $attributeHeight = 8;
    protected $borderWidth = 32;
    protected $borderHeight = 24;
    protected $rotation;
    protected $interlaceMultiplier = 0.75;

    public function __construct($sourceFilePath, $resultFilePath)
    {
        $this->sourceFilePath = $sourceFilePath;
        $this->resultFilePath = $resultFilePath;
    }

    public function setBorder($border)
    {
        $this->border = $border;
    }

    public function setSize($size)
    {
        $this->size = $size;
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

    protected function read16BitString()
    {
        if ($b1 = $this->read8BitString()) {
            if ($b2 = $this->read8BitString()) {
                return $b2 . $b1;
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

        $result = array();

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
        $colors = array();
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
        $gigaColors = array();
        foreach ($colors as &$zxColor1) {
            foreach ($colors as &$zxColor2) {
                $gigaColors[$zxColor1 . $zxColor2] = 0;
            }
        }

        $cache = array();
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
        $colors = array();
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
        $dstImage = false;

        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);

        if ($this->size == '0') {
            $dstWidth = $srcWidth * 0.1875;
            $dstHeight = $srcHeight * 0.1875;

            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        }
        if ($this->size == '1') {
            $dstWidth = $srcWidth / 4;
            $dstHeight = $srcHeight / 4;

            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        } elseif ($this->size == '2') {
            $dstImage = $srcImage;
        } elseif ($this->size == '4') {
            $dstWidth = $srcWidth * 2;
            $dstHeight = $srcHeight * 2;

            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        } elseif ($this->size == '3') {
            $dstWidth = $srcWidth * 2;
            $dstHeight = $srcHeight * 2;

            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

            for ($y = 0; $y < $dstHeight; $y = $y + 2) {

                for ($x = 0; $x < $dstWidth; $x++) {
                    $rgb = imagecolorat($dstImage, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    $r = ceil($r * $this->interlaceMultiplier);
                    $g = ceil($g * $this->interlaceMultiplier);
                    $b = ceil($b * $this->interlaceMultiplier);

                    $color = $r * 0x010000 + $g * 0x0100 + $b;

                    imagesetpixel($dstImage, $x, $y, $color);
                }
            }
        } elseif ($this->size == '5') {
            $haloImage = imagecreatetruecolor($srcWidth, $srcHeight);
            imagealphablending($haloImage, false);
            imagesavealpha($haloImage, true);
            imagecopyresampled($haloImage, $srcImage, 0, 0, 0, 0, $srcWidth, $srcHeight, $srcWidth, $srcHeight);
            imagefilter($haloImage, IMG_FILTER_GAUSSIAN_BLUR);

            for ($j = 0; $j < $srcHeight; $j++) {
                for ($i = 0; $i < $srcWidth; $i++) {
                    $rgb = imagecolorat($haloImage, $i, $j);

                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    $luminance = ((0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b)) / 2;
                    $color = ((int)(127 - $luminance)) * 0x1000000 + $rgb;
                    imagesetpixel($haloImage, $i, $j, $color);
                }
            }
            $dstWidth = $srcWidth * 2;
            $dstHeight = $srcHeight * 2;

            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);

            $blurImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagealphablending($blurImage, false);
            imagesavealpha($blurImage, true);
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

            $gaussian = array(array(1.0, 2.0, 1.0), array(2.0, 4.0, 2.0), array(1.0, 2.0, 1.0));
            imageconvolution($dstImage, $gaussian, 16, 0);
            imagecopyresampled($blurImage, $haloImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

            imagecopymerge($dstImage, $blurImage, 0, 0, 0, 0, $dstWidth, $dstHeight, 50);

            imagegammacorrect($dstImage, 1, 1.5);

            $vert1 = 1;
            $vert2 = 1;

            $ycounter = 0;
            for ($y = 0; $y < $dstHeight; $y++) {
                if ($y % 2) {
                    $int = 0.92;
                } else {
                    $int = 1;
                }

                if ($ycounter > 1) {
                    $ycounter = 0;
                    if ($vert1 == 1) {
                        $vert1 = 0.8;
                        $vert2 = 1;
                    } else {
                        $vert1 = 1;
                        $vert2 = 0.8;
                    }
                }
                $ycounter++;

                for ($x = 0; $x < $dstWidth; $x++) {
                    $rgb = imagecolorat($dstImage, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    if ($x % 2) {
                        $r = ceil($r * $vert1 * $int);
                        $g = ceil($g * $vert1 * $int);
                        $b = ceil($b * $vert1 * $int);
                    } else {
                        $r = ceil($r * $vert2 * $int);
                        $g = ceil($g * $vert2 * $int);
                        $b = ceil($b * $vert2 * $int);
                    }
                    $color = $r * 0x010000 + $g * 0x0100 + $b;

                    imagesetpixel($dstImage, $x, $y, $color);
                }
            }
        }

        return $dstImage;
    }

    protected function drawBorder($centerImage, $parsedData)
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
            $result = imagepng($image, $this->resultFilePath);
        }
        return $result;
    }

    abstract protected function loadBits();

    abstract protected function parseScreen($data);

    abstract protected function exportData($parsedData, $flashedImage = false);
}

class ConverterPlugin_standard extends ConverterPlugin
{
    protected $fileSize = 6912;

    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            if (count($parsedData['attributesData']['flashMap']) > 0) {
                $gifImages = array();

                $image = $this->exportData($parsedData, false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData, true);
                $gifImages[] = $this->getRightPaletteGif($image);

                $delays = array(32, 32);
                $result = $this->buildAnimatedGif($gifImages, $delays);
                file_put_contents($this->resultFilePath, $result);
            } else {
                $image = $this->exportData($parsedData, false);
                $result = imagepng($image, $this->resultFilePath);
            }
        }
        return $result;
    }

    protected function buildAnimatedGif($frames, $durations)
    {
        $gc = new \GifCreator\GifCreator();
        $gc->create($frames, $durations, 0);

        return $gc->getGif();
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        return $parsedData;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                if ($flashedImage && isset($parsedData['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel === '1') {
                        $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    }
                } else {
                    if ($pixel === '1') {
                        $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                }
                $color = $this->colors[$ZXcolor];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function parsePixels($pixelsArray)
    {
        $x = 0;
        $y = 0;
        $zxY = 0;
        $pixelsData = array();
        foreach ($pixelsArray as &$bits) {
            $offset = 0;
            while ($offset < 8) {
                $bit = substr($bits, $offset, 1);

                $pixelsData[$zxY][$x] = $bit;

                $offset++;
                $x++;
                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                    $zxY = $this->calculateZXY($y);
                }
            }
        }
        return $pixelsData;
    }

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array(), 'flashMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            $flashStatus = substr($bits, 0, 1);
            if ($flashStatus == '1') {
                $attributesData['flashMap'][$y][$x] = $flashStatus;
            }

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray);
            return $resultBits;
        }
        return false;
    }

    protected function calculateZXY($y)
    {
        $offset = 0;
        if ($y > 127) {
            $offset = 128;
            $y = $y - 128;
        } elseif ($y > 63) {
            $offset = 64;
            $y = $y - 64;
        }

        $rows = (int)($y / 8);

        $rests = $y - $rows * 8;

        $result = $offset + $rests * 8 + $rows;

        return $result;
    }

    protected function getRightPaletteGif($srcImage)
    {
        $temporaryFileName = 'test.gif';
        $palettedImage = imagecreate(imagesx($srcImage), imagesy($srcImage));
        imagecopy($palettedImage, $srcImage, 0, 0, 0, 0, imagesx($srcImage), imagesy($srcImage));
        imagecolormatch($srcImage, $palettedImage);
        imagegif($palettedImage, $temporaryFileName);
        $gifFile = file_get_contents($temporaryFileName);
        unlink($temporaryFileName);
        return $gifFile;
    }

    protected function interlaceMix(&$image1, &$image2, $lineHeight)
    {
        $multiplier = 1;
        if ($this->size == '3' || $this->size == '4') {
            $multiplier = 2;
        }

        $width = imagesx($image1);
        $height = imagesy($image1);

        for ($y = 0; $y < $height; $y++) {
            if ((int)($y / ($lineHeight * $multiplier)) % 2) {
                for ($x = 0; $x < $width; $x++) {
                    $pixel1 = imagecolorat($image1, $x, $y);
                    $pixel2 = imagecolorat($image2, $x, $y);

                    imagesetpixel($image2, $x, $y, $pixel1);
                    imagesetpixel($image1, $x, $y, $pixel2);
                }
            }
        }
    }
}

class ConverterPlugin_hidden extends ConverterPlugin_standard
{
    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                if ($flashedImage && isset($parsedData['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel === '1') {
                        $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    }
                } else {
                    if ($parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX] == $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX]) {
                        if ($pixel === '1') {
                            $ZXcolor = 'hidden';
                        } else {
                            $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                        }
                    } else {
                        if ($pixel === '1') {
                            $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                        } else {
                            $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                        }
                    }
                }
                if ($ZXcolor == 'hidden') {
                    $color = 0xFF8000;
                } else {
                    $color = $this->colors[$ZXcolor];
                }
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}

class ConverterPlugin_ulaplus extends ConverterPlugin_standard
{
    protected $fileSize = 6976;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        $paletteArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } elseif ($length < 6912) {
                    $attributesArray[] = $bin;
                } else {
                    $paletteArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array(
                'pixelsArray'     => $pixelsArray,
                'attributesArray' => $attributesArray,
                'paletteArray'    => $paletteArray,
            );
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseUlaPlusPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parseUlaPlusPalette($paletteArray)
    {
        $paletteData = array();
        foreach ($paletteArray as &$ulaColor) {
            $r = bindec(substr($ulaColor, 3, 3)) * 32;
            $g = bindec(substr($ulaColor, 0, 3)) * 32;
            $b = bindec(substr($ulaColor, 6, 2)) * 64;

            $redChannel = round(
                ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
            );
            $greenChannel = round(
                ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
            );
            $blueChannel = round(
                ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
            );

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;

            $paletteData[] = $RGB;
        }
        return $paletteData;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                if ($pixel === '1') {
                    $ZXcolor = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                } else {
                    $ZXcolor = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                }

                $color = $parsedData['colorsData'][$ZXcolor];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 5, 3));
            $paper = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 2, 3)) + 8;

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }
}

class ConverterPlugin_sam4 extends ConverterPlugin_standard
{
    protected $fileSize = 24617;

    protected function loadBits()
    {
        $pixelsArray = array();
        $paletteArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 256 * 192 / 2) {
                    $pixelsArray[] = $bin;
                } elseif ($length < 256 * 192 / 2 + 16) {
                    $paletteArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array(
                'pixelsArray'  => $pixelsArray,
                'paletteArray' => $paletteArray,
            );
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseSam4Palette($data['paletteArray']);
        return $parsedData;
    }

    protected function parsePixels($pixelsArray)
    {
        $x = 0;
        $y = 0;
        $pixelsData = array();
        foreach ($pixelsArray as &$bits) {
            $p1 = substr($bits, 0, 4);
            $pixelsData[$y][$x] = $p1;
            $x++;
            $p2 = substr($bits, 4, 4);
            $pixelsData[$y][$x] = $p2;
            $x++;

            if ($x >= $this->width) {
                $x = 0;
                $y++;
            }
        }
        return $pixelsData;

    }

    protected function parseSam4Palette($paletteArray)
    {
        $m = 36;
        $paletteData = array();
        foreach ($paletteArray as &$clutItem) {
            $bright = (int)substr($clutItem, 4, 1);
            $r = ((int)substr($clutItem, 2, 1) * 4 + (int)substr($clutItem, 6, 1) * 2 + $bright) * $m;
            $g = ((int)substr($clutItem, 1, 1) * 4 + (int)substr($clutItem, 5, 1) * 2 + $bright) * $m;
            $b = ((int)substr($clutItem, 3, 1) * 4 + (int)substr($clutItem, 7, 1) * 2 + $bright) * $m;

            $redChannel = round(
                ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
            );
            $greenChannel = round(
                ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
            );
            $blueChannel = round(
                ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
            );

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;

            $paletteData[] = $RGB;
        }
        return $paletteData;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $color = $parsedData['colorsData'][bindec($pixel)];
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 5, 3));
            $paper = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 2, 3)) + 8;

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }
}

class ConverterPlugin_zxevo extends ConverterPlugin
{
    protected $width = 320;
    protected $height = 200;

    public function convert()
    {
        if ($gdObject = $this->loadBits()) {
            $image = $this->adjustImage($gdObject);
            imagepng($image, $this->resultFilePath);
        }
    }

    protected function loadBits()
    {
        if (file_exists($this->sourceFilePath)) {
            $gdObject = imagecreatefrombmp($this->sourceFilePath);
            return $gdObject;
        }
        return false;
    }

    protected function adjustImage($image)
    {
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $rgb = imagecolorat($image, $x, $y);

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $redChannel = round(
                    ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
                );
                $greenChannel = round(
                    ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
                );
                $blueChannel = round(
                    ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
                );

                $color = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
                imagesetpixel($image, $x, $y, $color);
            }
        }

        $resultImage = $this->resizeImage($image);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 5, 3));
            $paper = bindec(substr($bits, 0, 2)) * 16 + bindec(substr($bits, 2, 3)) + 8;

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

    protected function parseScreen($data) { }

    protected function exportData($parsedData, $flashedImage = false) { }
}


class ConverterPlugin_sxg extends ConverterPlugin
{
    const FORMAT_256 = 2;
    const FORMAT_16 = 1;
    protected $sxgFormat = 2;

    protected $table = [
        0  => 0,
        1  => 10,
        2  => 21,
        3  => 31,
        4  => 42,
        5  => 53,
        6  => 63,
        7  => 74,
        8  => 85,
        9  => 95,
        10 => 106,
        11 => 117,
        12 => 127,
        13 => 138,
        14 => 149,
        15 => 159,
        16 => 170,
        17 => 181,
        18 => 191,
        19 => 202,
        20 => 213,
        21 => 223,
        22 => 234,
        23 => 245,
        24 => 255,
    ];

    protected function loadBits()
    {
        if (file_exists($this->sourceFilePath)) {
            $this->handle = fopen($this->sourceFilePath, "rb");
            $resultBits = array(
                'pixelsArray'  => [],
                'paletteArray' => [],
            );
            $firstByte = $this->readByte();
            $signature = $this->readString(3);
            if ($firstByte == 127 && $signature == 'SXG') {
                $this->readByte(); //version
                $this->readByte(); //background
                $this->readByte(); //packed
                $this->sxgFormat = $this->readByte();
                $this->width = $this->readWord();
                $this->height = $this->readWord();
                $paletteShift = $this->readWord();
                $pixelsShift = $this->readWord();

                $this->readBytes($paletteShift - 2);
                $paletteArray = array();
                $paletteLength = ($pixelsShift - $paletteShift + 2) / 2;
                while ($paletteLength > 0) {
                    $paletteArray[] = $this->read16BitString();
                    $paletteLength--;
                }

                $pixelsArray = array();
                while (($word = $this->readByte()) !== false) {
                    $pixelsArray[] = $word;
                }

                $resultBits['pixelsArray'] = $pixelsArray;
                $resultBits['paletteArray'] = $paletteArray;
            }
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['colorsData'] = $this->parseSxgPalette($data['paletteArray']);
        return $parsedData;
    }

    protected function parsePixels($pixelsArray)
    {
        $x = 0;
        $y = 0;
        $pixelsData = array();
        if ($this->sxgFormat === self::FORMAT_16) {
            foreach ($pixelsArray as &$bits) {
                $bits = str_pad(decbin($bits), 8, '0', STR_PAD_LEFT);
                $pixelsData[$y][$x] = bindec(substr($bits, 0, 4));
                $x++;
                $pixelsData[$y][$x] = bindec(substr($bits, 4, 4));
                $x++;

                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                }
            }
        } elseif ($this->sxgFormat === self::FORMAT_256) {
            foreach ($pixelsArray as &$pixel) {
                $pixelsData[$y][$x] = $pixel;
                $x++;
                if ($x >= $this->width) {
                    $x = 0;
                    $y++;
                }
            }
        }
        return $pixelsData;

    }

    protected function parseSxgPalette($paletteArray)
    {
        $paletteData = array();
        foreach ($paletteArray as &$clutItem) {
            if (substr($clutItem, 0, 1) == '0') {
                $r = $this->table[bindec(substr($clutItem, 1, 5))];
                $g = $this->table[bindec(substr($clutItem, 6, 5))];
                $b = $this->table[bindec(substr($clutItem, 11, 5))];
            } else {
                $r = bindec(substr($clutItem, 1, 5)) << 3;
                $g = bindec(substr($clutItem, 6, 5)) << 3;
                $b = bindec(substr($clutItem, 11, 5)) << 3;
            }
            $redChannel = round(
                ($r * $this->palette['R11'] + $g * $this->palette['R12'] + $b * $this->palette['R13']) / 0xFF
            );
            $greenChannel = round(
                ($r * $this->palette['R21'] + $g * $this->palette['R22'] + $b * $this->palette['R23']) / 0xFF
            );
            $blueChannel = round(
                ($r * $this->palette['R31'] + $g * $this->palette['R32'] + $b * $this->palette['R33']) / 0xFF
            );

            $RGB = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;

            $paletteData[] = $RGB;
        }
        return $paletteData;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                imagesetpixel($image, $x, $y, $parsedData['colorsData'][$pixel]);
            }
        }

        $resultImage = $this->resizeImage($image);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

}


class ConverterPlugin_bsc extends ConverterPlugin_standard
{
    protected $attributesLength = 768;
    protected $borderWidth = 64;
    protected $borderHeight = 56;
    protected $fileSize = 11136;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        $borderArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } elseif ($length < 6144 + $this->attributesLength) {
                    $attributesArray[] = $bin;
                } else {
                    $borderArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array(
                'pixelsArray'     => $pixelsArray,
                'attributesArray' => $attributesArray,
                'borderArray'     => $borderArray
            );
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['borderData'] = $data['borderArray'];
        return $parsedData;
    }

    protected function drawBorder($centerImage, $parsedData)
    {
        if (is_numeric($this->border)) {
            $resultImage = imagecreatetruecolor(
                $this->width + $this->borderWidth * 2,
                $this->height + $this->borderHeight * 2
            );

            $x = 0;
            $y = 0;

            foreach ($parsedData['borderData'] as $byte) {
                $left = "0" . substr($byte, 5, 3);

                $code = sprintf('%04.0f', $left);
                $color = $this->colors[$code];
                for ($i = 0; $i < 8; $i++) {
                    imagesetpixel($resultImage, $x + $i, $y, $color);
                }

                $x = $x + 8;
                $right = "0" . substr($byte, 2, 3);
                $code = sprintf('%04.0f', $right);
                $color = $this->colors[$code];
                for ($i = 0; $i < 8; $i++) {
                    imagesetpixel($resultImage, $x + $i, $y, $color);
                }

                $x = $x + 8;
                //skip central pixels for center of image
                if ($y >= ($this->borderHeight + 8) && $y < ($this->height + $this->borderHeight + 8) && $x == $this->borderWidth) {
                    $x = $x + $this->width;
                }

                if ($x >= $this->width + $this->borderWidth * 2) {
                    $x = 0;
                    $y++;
                }
            }

            imagecopy(
                $resultImage,
                $centerImage,
                $this->borderWidth,
                $this->borderHeight + 8,
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
}

class ConverterPlugin_bmc4 extends ConverterPlugin_bsc
{
    protected $attributesLength = 1536;
    protected $attributeHeight = 4;
    protected $fileSize = 11904;

    protected function loadBits()
    {
        if ($resultBits = parent::loadBits()) {
            $attributesArray = array();
            for ($j = 0; $j < 24; $j++) {
                for ($i = 0; $i < 32; $i++) {
                    $attributesArray[] = $resultBits['attributesArray'][$j * 32 + $i];
                }
                for ($i = 0; $i < 32; $i++) {
                    $attributesArray[] = $resultBits['attributesArray'][768 + $j * 32 + $i];
                }
            }
            $resultBits['attributesArray'] = $attributesArray;
        }
        return $resultBits;
    }

}

class ConverterPlugin_gigascreen extends ConverterPlugin_standard
{
    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData1 = $this->parseScreen($bits[0]);
            $parsedData2 = $this->parseScreen($bits[1]);

            $gifImages = array();

            if ($this->gigascreenMode == 'flicker' || $this->gigascreenMode == 'interlace1' || $this->gigascreenMode == 'interlace2') {
                if (count($parsedData1['attributesData']['flashMap']) > 0 || count(
                        $parsedData2['attributesData']['flashMap']
                    ) > 0
                ) {
                    $image1 = $this->exportData($parsedData1, false);
                    $image2 = $this->exportData($parsedData2, false);
                    $image1f = $this->exportData($parsedData1, true);
                    $image2f = $this->exportData($parsedData2, true);

                    if ($this->gigascreenMode == 'interlace1') {
                        $this->interlaceMix($image1, $image2, 1);
                        $this->interlaceMix($image1f, $image2f, 1);
                    } elseif ($this->gigascreenMode == 'interlace2') {
                        $this->interlaceMix($image1, $image2, 2);
                        $this->interlaceMix($image1f, $image2f, 2);
                    }

                    $frame1 = $this->getRightPaletteGif($image1);
                    $frame2 = $this->getRightPaletteGif($image2);
                    $frame1f = $this->getRightPaletteGif($image1f);
                    $frame2f = $this->getRightPaletteGif($image2f);

                    $delays = array();
                    for ($i = 0; $i < 32; $i++) {
                        if ($i < 16) {
                            if ($i & 1) {
                                $gifImages[] = $frame1;
                            } else {
                                $gifImages[] = $frame2;
                            }
                        } else {
                            if ($i & 1) {
                                $gifImages[] = $frame1f;
                            } else {
                                $gifImages[] = $frame2f;
                            }
                        }
                        $delays[] = 2;
                    }

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                    file_put_contents($this->resultFilePath, $result);
                } else {
                    $image1 = $this->exportData($parsedData1, false);
                    $image2 = $this->exportData($parsedData2, false);

                    if ($this->gigascreenMode == 'interlace1') {
                        $this->interlaceMix($image1, $image2, 1);
                    } elseif ($this->gigascreenMode == 'interlace2') {
                        $this->interlaceMix($image1, $image2, 2);
                    }

                    $gifImages[] = $this->getRightPaletteGif($image1);
                    $gifImages[] = $this->getRightPaletteGif($image2);

                    $delays = array(2, 2);

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                    file_put_contents($this->resultFilePath, $result);
                }
            } else {
                if (count($parsedData1['attributesData']['flashMap']) > 0 || count(
                        $parsedData2['attributesData']['flashMap']
                    ) > 0
                ) {
                    $image1 = $this->exportDataMerged($parsedData1, $parsedData2, false);
                    $gifImages[] = $this->getRightPaletteGif($image1);

                    $image2 = $this->exportDataMerged($parsedData1, $parsedData2, true);
                    $gifImages[] = $this->getRightPaletteGif($image2);

                    $delays = array(32, 32);

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                    file_put_contents($this->resultFilePath, $result);
                } else {
                    $image = $this->exportDataMerged($parsedData1, $parsedData2, false);
                    $result = imagepng($image, $this->resultFilePath);
                }
            }
        }
        return $result;
    }

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 13824) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            $firstImage = false;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
                if ($length == 6912 && !$firstImage) {
                    $firstImage = array();
                    $firstImage['pixelsArray'] = $pixelsArray;
                    $firstImage['attributesArray'] = $attributesArray;

                    $pixelsArray = array();
                    $attributesArray = array();
                    $length = 0;
                }
            }
            $secondImage = array();
            $secondImage['pixelsArray'] = $pixelsArray;
            $secondImage['attributesArray'] = $attributesArray;
            $resultBits = array($firstImage, $secondImage);
            return $resultBits;
        }
        return false;
    }

    protected function exportDataMerged($parsedData1, $parsedData2, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData1['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel1) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                $pixel2 = $parsedData2['pixelsData'][$y][$x];
                if ($flashedImage && isset($parsedData1['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel1 === '1') {
                        $ZXcolor = $parsedData1['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData1['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    }
                } else {
                    if ($pixel1 === '1') {
                        $ZXcolor = $parsedData1['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor = $parsedData1['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                }

                if ($flashedImage && isset($parsedData2['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel2 === '1') {
                        $ZXcolor .= $parsedData2['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor .= $parsedData2['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    }
                } else {
                    if ($pixel2 === '1') {
                        $ZXcolor .= $parsedData2['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $ZXcolor .= $parsedData2['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                }

                $color = $this->gigaColors[$ZXcolor];
                imagesetpixel($image, $x, $y, $color);
            }
        }
        $resultImage = $this->drawBorder($image, $parsedData1);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }
}

class ConverterPlugin_chrd extends ConverterPlugin_gigascreen
{
    protected $colorType;

    public function convert()
    {
        $result = false;
        $this->loadBits();
        if ($this->colorType == '9') {
            if ($bits = $this->loadBits()) {
                $parsedData = $this->parseScreen($bits);
                if (count($parsedData['attributesData']['flashMap']) > 0) {
                    $gifImages = array();

                    $image = $this->exportData($parsedData, false);
                    $gifImages[] = $this->getRightPaletteGif($image);

                    $image = $this->exportData($parsedData, true);
                    $gifImages[] = $this->getRightPaletteGif($image);

                    $delays = array(32, 32);
                    $result = $this->buildAnimatedGif($gifImages, $delays);
                    file_put_contents($this->resultFilePath, $result);
                } else {
                    $image = $this->exportData($parsedData, false);
                    $result = imagepng($image, $this->resultFilePath);
                }
            }
        } elseif ($this->colorType == '18') {
            $result = parent::convert();
        }
        return $result;
    }

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        $pixelsArray2 = array();
        $attributesArray2 = array();
        if (file_exists($this->sourceFilePath)) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            $signature = '';

            while ($bin = $this->readChar()) {
                $signature .= $bin;

                $length++;
                if ($length >= 4) {
                    break;
                }
            }
            if (strtolower($signature) == 'chr$') {
                $this->width = $this->readByte() * 8;
                $this->height = $this->readByte() * 8;
                $this->colorType = $this->readByte();

                for ($y = 0; $y < $this->height / 8; $y++) {
                    for ($x = 0; $x < $this->width / 8; $x++) {
                        if ($this->colorType == '8') {
                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray[] = $this->read8BitString();
                            }
                        }
                        if ($this->colorType == '9') {
                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray[] = $this->read8BitString();
                            }
                            $attributesArray[] = $this->read8BitString();
                        }
                        if ($this->colorType == '18') {
                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray[] = $this->read8BitString();
                            }
                            $attributesArray[] = $this->read8BitString();

                            for ($i = 0; $i < 8; $i++) {
                                $pixelsArray2[] = $this->read8BitString();
                            }
                            $attributesArray2[] = $this->read8BitString();
                        }
                    }
                }
            }

            if ($this->colorType == '8') {
                $resultBits = array(
                    'pixelsArray' => $pixelsArray
                );
            } elseif ($this->colorType == '9') {
                $resultBits = array(
                    'pixelsArray'     => $pixelsArray,
                    'attributesArray' => $attributesArray
                );
            } elseif ($this->colorType == '18') {
                $resultBits = array(
                    array('pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray),
                    array('pixelsArray' => $pixelsArray2, 'attributesArray' => $attributesArray2),
                );
            } else {
                $resultBits = array();
            }
            return $resultBits;
        }
        return false;
    }

    protected function parsePixels($pixelsArray)
    {
        $pixelsData = array();

        $x = 0;
        $y = 0;
        $yOffset = 0;
        foreach ($pixelsArray as &$bits) {
            $xOffset = 0;
            while ($xOffset < 8) {
                $bit = substr($bits, $xOffset, 1);

                $pixelsData[$y + $yOffset][$x + $xOffset] = $bit;

                $xOffset++;
            }
            $yOffset++;
            if ($yOffset >= 8) {
                $yOffset = 0;
                $x = $x + 8;
                if ($x >= $this->width) {
                    $x = 0;
                    $y = $y + 8;
                }
            }
        }
        return $pixelsData;
    }
}

class ConverterPlugin_monochrome extends ConverterPlugin_standard
{
    protected $inkColorZX = '000';
    protected $paperColorZX = '111';
    protected $brightnessZX = '1';

    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);

            $image = $this->exportData($parsedData, false);
            imagegif($image, $this->resultFilePath);
        }
        return $result;
    }

    protected function loadBits()
    {
        $pixelsArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) >= 6144) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray);
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        $parsedData['attributesData'] = $this->generateAttributesArray();
        return $parsedData;
    }

    protected function generateAttributesArray()
    {
        $inkColorCode = $this->brightnessZX . $this->inkColorZX;
        $paperColorCode = $this->brightnessZX . $this->paperColorZX;
        $attributesData = array();
        for ($y = 0; $y < 24; $y++) {
            for ($x = 0; $x < 32; $x++) {
                $attributesData['inkMap'][$y][$x] = $inkColorCode;
                $attributesData['paperMap'][$y][$x] = $paperColorCode;
            }
        }
        $attributesData['flashMap'] = array();
        return $attributesData;
    }
}

class ConverterPlugin_flash extends ConverterPlugin_standard
{
    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);

            $image = $this->exportData($parsedData);
            $result = imagegif($image, $this->resultFilePath);
        }
        return $result;
    }

    protected function exportData($parsedData, $flashedImage = false)
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        foreach ($parsedData['pixelsData'] as $y => &$row) {
            foreach ($row as $x => &$pixel) {
                $mapPositionX = (int)($x / $this->attributeWidth);
                $mapPositionY = (int)($y / $this->attributeHeight);

                if (isset($parsedData['attributesData']['flashMap'][$mapPositionY][$mapPositionX])) {
                    if ($pixel === '1') {
                        $colorZX = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                        $colorZX .= $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                        $color = $this->gigaColors[$colorZX];
                    } else {
                        $colorZX = '0000';
                        $color = $this->colors[$colorZX];
                    }

                } else {
                    if ($pixel === '1') {
                        $colorZX = $parsedData['attributesData']['inkMap'][$mapPositionY][$mapPositionX];
                    } else {
                        $colorZX = $parsedData['attributesData']['paperMap'][$mapPositionY][$mapPositionX];
                    }
                    $color = $this->colors[$colorZX];
                }

                imagesetpixel($image, $x, $y, $color);
            }
        }
        $resultImage = $this->drawBorder($image, $parsedData);
        $resultImage = $this->resizeImage($resultImage);
        $resultImage = $this->checkRotation($resultImage);
        return $resultImage;
    }

}

class ConverterPlugin_tricolor extends ConverterPlugin_standard
{
    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);

            if ($this->gigascreenMode == 'flicker') {
                $gifImages = array();
                $image = $this->exportData($parsedData[0], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData[1], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData[2], false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $delays = array(2, 2, 2);

                $result = $this->buildAnimatedGif($gifImages, $delays);
                file_put_contents($this->resultFilePath, $result);
            } else {
                $resources = array();
                $resources[] = $this->exportData($parsedData[0], false);
                $resources[] = $this->exportData($parsedData[1], false);
                $resources[] = $this->exportData($parsedData[2], false);

                $result = $this->buildMixedPng($resources);
                file_put_contents($this->resultFilePath, $result);
            }
        }
        return $result;
    }

    protected function buildMixedPng($resources)
    {
        $first = reset($resources);
        $width = imagesx($first);
        $height = imagesy($first);
        $mixed = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $overall = 0;
                foreach ($resources as &$resource) {
                    $color = imagecolorat($resource, $x, $y);
                    $overall = $overall + $color;
                }
                imagesetpixel($mixed, $x, $y, $overall);
            }
        }
        $temporary = 'test.png';
        imagepng($mixed, $temporary);

        $result = file_get_contents($temporary);
        unlink($temporary);
        return $result;
    }

    protected function loadBits()
    {
        $pixelsArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 6144 * 3) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            $image = 0;
            while ($bin = $this->read8BitString()) {
                if ($length == 6144) {
                    $length = 0;
                    $image++;
                    $pixelsArray[$image] = array();
                }
                $pixelsArray[$image][] = $bin;
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray);
            return $resultBits;
        }
        return false;
    }

    protected function parseScreen($data)
    {
        $parsedData = array();
        $parsedData[0]['pixelsData'] = $this->parsePixels($data['pixelsArray'][0]);
        $parsedData[0]['attributesData'] = $this->generateAttributesArray('1010', '0000');
        $parsedData[1]['pixelsData'] = $this->parsePixels($data['pixelsArray'][1]);
        $parsedData[1]['attributesData'] = $this->generateAttributesArray('1100', '0000');
        $parsedData[2]['pixelsData'] = $this->parsePixels($data['pixelsArray'][2]);
        $parsedData[2]['attributesData'] = $this->generateAttributesArray('1001', '0000');
        return $parsedData;
    }

    protected function generateAttributesArray($inkColorCode, $paperColorCode)
    {
        $attributesData = array();
        for ($y = 0; $y < 24; $y++) {
            for ($x = 0; $x < 32; $x++) {
                $attributesData['inkMap'][$y][$x] = $inkColorCode;
                $attributesData['paperMap'][$y][$x] = $paperColorCode;
            }
        }
        $attributesData['flashMap'] = array();
        return $attributesData;
    }
}

class ConverterPlugin_multicolor extends ConverterPlugin_standard
{
    protected $attributeHeight = 2;
    protected $fileSize = 9216;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == $this->fileSize) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray);
            return $resultBits;
        }
        return false;
    }
}

class ConverterPlugin_multicolor4 extends ConverterPlugin_multicolor
{
    protected $attributeHeight = 4;
    protected $fileSize = 7680;
}

class ConverterPlugin_multiartist extends ConverterPlugin_gigascreen
{
    protected $mghMode = false;
    protected $borders = array();
    protected $mghMixedBorder = false;

    protected function parseScreen($data)
    {
        if ($this->mghMode == 1) {
            $parsedData = array();
            $parsedData['attributesData'] = $this->parseMGH1Attributes(
                $data['attributesArray'],
                $data['outerAttributesArray']
            );
            $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        } else {
            $parsedData = array();
            $parsedData['attributesData'] = $this->parseAttributes($data['attributesArray']);
            $parsedData['pixelsData'] = $this->parsePixels($data['pixelsArray']);
        }

        return $parsedData;
    }

    protected function parseMGH1Attributes($attributesArray, $outerArray)
    {
        $x = 8;
        $y = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array(), 'flashMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);

            $attributesData['inkMap'][$y][$x] = $ink;
            $attributesData['paperMap'][$y][$x] = $paper;

            $flashStatus = substr($bits, 0, 1);
            if ($flashStatus == '1') {
                $attributesData['flashMap'][$y][$x] = $flashStatus;
            }

            if ($x == 23) {
                $x = 8;
                $y++;
            } else {
                $x++;
            }
        }

        $x = 0;
        $y = 0;
        foreach ($outerArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);
            $flashStatus = substr($bits, 0, 1);

            for ($i = 0; $i < 8; $i++) {
                $attributesData['inkMap'][$y + $i][$x] = $ink;
                $attributesData['paperMap'][$y + $i][$x] = $paper;
                if ($flashStatus == '1') {
                    $attributesData['flashMap'][$y + $i][$x] = $flashStatus;
                }
            }
            if ($x == 7) {
                $x = 24;
            } elseif ($x == 31) {
                $x = 0;
                $y += 8;
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

    protected function loadBits()
    {
        if (file_exists($this->sourceFilePath)) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            $header = '';
            while ($string = fgetc($this->handle)) {
                $header .= $string;
                $length++;
                if ($length == 256) {
                    break;
                }
            }
            $signature = substr($header, 0, 3);
            $version = ord(substr($header, 3, 1));
            if (is_numeric($this->border)) {
                $this->borders[0] = ord(substr($header, 5, 1));
                $this->borders[1] = ord(substr($header, 6, 1));
            } else {
                $this->borders[0] = false;
                $this->borders[1] = false;
            }
            $this->mghMode = ord(substr($header, 4, 1));

            $pixelsLength = 6144;
            $outerAttributesLength = 0;

            $attributesLength = 768;
            if ($this->mghMode == 1) {
                $this->attributeHeight = 1;
                $attributesLength = 3072;
                $outerAttributesLength = 384;
            }
            if ($this->mghMode == 2) {
                $this->attributeHeight = 2;
                $attributesLength = 3072;
            }
            if ($this->mghMode == 4) {
                $this->attributeHeight = 4;
                $attributesLength = 1536;
            }
            if ($this->mghMode == 8) {
                $this->attributeHeight = 8;
                $attributesLength = 768;
            }

            if ($signature == 'MGH' && $version == '1') {
                $firstImage = array();
                $secondImage = array();

                if ($this->mghMode == 1) {
                    $length = 0;

                    while ($bin = $this->read8BitString()) {
                        $bytesArray[] = $bin;

                        $length++;
                        if ($length == $pixelsLength) {
                            $firstImage['pixelsArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2) {
                            $secondImage['pixelsArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength) {
                            $firstImage['attributesArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2) {
                            $secondImage['attributesArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2 + $outerAttributesLength) {
                            $firstImage['outerAttributesArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2 + $outerAttributesLength * 2) {
                            $secondImage['outerAttributesArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                    }
                } else {
                    $length = 0;

                    while ($bin = $this->read8BitString()) {
                        $bytesArray[] = $bin;

                        $length++;
                        if ($length == $pixelsLength) {
                            $firstImage['pixelsArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2) {
                            $secondImage['pixelsArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength) {
                            $firstImage['attributesArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                        if ($length == $pixelsLength * 2 + $attributesLength * 2) {
                            $secondImage['attributesArray'] = $bytesArray;
                            $bytesArray = array();
                        }
                    }
                }
                $resultBits = array($firstImage, $secondImage);
                return $resultBits;
            }
        }
        return false;
    }

    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData1 = $this->parseScreen($bits[0]);
            $parsedData2 = $this->parseScreen($bits[1]);

            $gifImages = array();

            if ($this->gigascreenMode == 'flicker' || $this->gigascreenMode == 'interlace1' || $this->gigascreenMode == 'interlace2') {
                if (count($parsedData1['attributesData']['flashMap']) > 0 || count(
                        $parsedData2['attributesData']['flashMap']
                    ) > 0
                ) {
                    $this->border = $this->borders[0];
                    $image1 = $this->exportData($parsedData1, false);

                    $this->border = $this->borders[1];
                    $image2 = $this->exportData($parsedData2, false);

                    $this->border = $this->borders[0];
                    $image1f = $this->exportData($parsedData1, true);

                    $this->border = $this->borders[1];
                    $image2f = $this->exportData($parsedData2, true);

                    if ($this->gigascreenMode == 'interlace1') {
                        $this->interlaceMix($image1, $image2, 1);
                        $this->interlaceMix($image1f, $image2f, 1);
                    } elseif ($this->gigascreenMode == 'interlace2') {
                        $this->interlaceMix($image1, $image2, 2);
                        $this->interlaceMix($image1f, $image2f, 2);
                    }

                    $frame1 = $this->getRightPaletteGif($image1);
                    $frame2 = $this->getRightPaletteGif($image2);
                    $frame1f = $this->getRightPaletteGif($image1f);
                    $frame2f = $this->getRightPaletteGif($image2f);

                    $delays = array();
                    for ($i = 0; $i < 32; $i++) {
                        if ($i < 16) {
                            if ($i & 1) {
                                $gifImages[] = $frame1;
                            } else {
                                $gifImages[] = $frame2;
                            }
                        } else {
                            if ($i & 1) {
                                $gifImages[] = $frame1f;
                            } else {
                                $gifImages[] = $frame2f;
                            }
                        }
                        $delays[] = 2;
                    }

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                    file_put_contents($this->resultFilePath, $result);
                } else {
                    $this->border = $this->borders[0];
                    $image1 = $this->exportData($parsedData1, false);

                    $this->border = $this->borders[1];
                    $image2 = $this->exportData($parsedData2, false);

                    if ($this->gigascreenMode == 'interlace1') {
                        $this->interlaceMix($image1, $image2, 1);
                    } elseif ($this->gigascreenMode == 'interlace2') {
                        $this->interlaceMix($image1, $image2, 2);
                    }

                    $gifImages[] = $this->getRightPaletteGif($image1);
                    $gifImages[] = $this->getRightPaletteGif($image2);

                    $delays = array(2, 2);

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                    file_put_contents($this->resultFilePath, $result);
                }
            } else {
                if (count($parsedData1['attributesData']['flashMap']) > 0 || count(
                        $parsedData2['attributesData']['flashMap']
                    ) > 0
                ) {
                    $this->mghMixedBorder = true;
                    $image1 = $this->exportDataMerged($parsedData1, $parsedData2, false);
                    $gifImages[] = $this->getRightPaletteGif($image1);

                    $this->mghMixedBorder = true;
                    $image2 = $this->exportDataMerged($parsedData1, $parsedData2, true);
                    $gifImages[] = $this->getRightPaletteGif($image2);

                    $delays = array(32, 32);

                    $result = $this->buildAnimatedGif($gifImages, $delays);
                    file_put_contents($this->resultFilePath, $result);
                } else {
                    $this->mghMixedBorder = true;
                    $image = $this->exportDataMerged($parsedData1, $parsedData2, false);
                    $result = imagepng($image, $this->resultFilePath);
                }
            }
        }
        return $result;
    }

    protected function drawBorder($centerImage, $parsedData)
    {
        if (is_numeric($this->borders[0]) && is_numeric($this->borders[1]) && $this->mghMixedBorder == true) {
            $resultImage = imagecreatetruecolor(320, 240);
            $code1 = sprintf('%04.0f', decbin($this->borders[0]));
            $code2 = sprintf('%04.0f', decbin($this->borders[1]));
            $color = $this->gigaColors[$code1 . $code2];
            imagefill($resultImage, 0, 0, $color);
            imagecopy($resultImage, $centerImage, 32, 24, 0, 0, $this->width, $this->height);
        } else {
            $resultImage = parent::drawBorder($centerImage, $parsedData);
        }
        return $resultImage;
    }
}

class ConverterPlugin_attributesm extends ConverterPlugin_multiartist
{
    protected function loadBits()
    {
        $this->attributeHeight = 2;
        $this->mghMode = 2;

        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 768) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            while ($bin = $this->read8BitString()) {
                $attributesArray[] = $bin;
            }
            $attributesArray = array_merge($attributesArray, $attributesArray, $attributesArray, $attributesArray);
            $resultBits = array(
                array('pixelsArray' => $this->generatePixelsArray(true), 'attributesArray' => $attributesArray),
                array('pixelsArray' => $this->generatePixelsArray(false), 'attributesArray' => $attributesArray),
            );
            return $resultBits;
        }
        return false;
    }

    protected function generatePixelsArray($invert)
    {
        $pixelsArray = array();
        if ($invert) {
            $first = '01010101';
            $second = '10101010';
        } else {
            $second = '01010101';
            $first = '10101010';
        }
        for ($third = 0; $third < 3; $third++) {
            for ($y = 0; $y < 4; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = $first;
                }
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = $second;
                }
            }
        }
        return $pixelsArray;
    }
}

class ConverterPlugin_attributes extends ConverterPlugin_standard
{
    protected function loadBits()
    {
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 768) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            while ($bin = $this->read8BitString()) {
                $attributesArray[] = $bin;
            }
            $resultBits = array('pixelsArray' => $this->generatePixelsArray(), 'attributesArray' => $attributesArray);
            return $resultBits;
        }
        return false;
    }

    protected function generatePixelsArray()
    {
        $pixelsArray = array();
        for ($third = 0; $third < 3; $third++) {
            for ($y = 0; $y < 4; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = '01010101';
                }
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = '10101010';
                }
            }
        }
        return $pixelsArray;
    }

    public function convert()
    {
        $result = false;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            if (count($parsedData['attributesData']['flashMap']) > 0) {
                $gifImages = array();

                $image = $this->exportData($parsedData, false);
                $gifImages[] = $this->getRightPaletteGif($image);

                $image = $this->exportData($parsedData, true);
                $gifImages[] = $this->getRightPaletteGif($image);

                $delays = array(32, 32);
                $result = $this->buildAnimatedGif($gifImages, $delays);
                file_put_contents($this->resultFilePath, $result);
            } else {
                $image = $this->exportData($parsedData, false);
                $result = imagepng($image, $this->resultFilePath);
            }
        }
        return $result;
    }
}

class ConverterPlugin_lowresgs extends ConverterPlugin_gigascreen
{
    protected function loadBits()
    {
        $texture = array();
        $attributesArray = array(array(), array());
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 1628) {
            $this->handle = fopen($this->sourceFilePath, "rb");
            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length >= 84 && $length < 92) {
                    $texture[] = $bin;
                } elseif ($length >= 92 && $length < 92 + 768) {
                    $attributesArray[0][] = $bin;
                } elseif ($length >= 92 + 768) {
                    $attributesArray[1][] = $bin;
                }
                $length++;
            }
            $pixelsArray = $this->generatePixelsArray($texture);
            $resultBits = array(
                $resultBits = array(
                    'pixelsArray'     => $pixelsArray,
                    'attributesArray' => $attributesArray[0],
                ),
                array(
                    'pixelsArray'     => $pixelsArray,
                    'attributesArray' => $attributesArray[1],
                ),
            );
            return $resultBits;
        }
        return false;
    }

    protected function generatePixelsArray($texture)
    {
        $pixelsArray = array();
        for ($third = 0; $third < 3; $third++) {
            $row = 0;
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 32 * 8; $x++) {
                    $pixelsArray[] = $texture[$row];
                }
                $row++;
            }
        }
        return $pixelsArray;
    }
}

class ConverterPlugin_mc extends ConverterPlugin_standard
{
    protected $attributeHeight = 1;

    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 12288) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray);
            return $resultBits;
        }
        return false;
    }

    protected function calculateZXY($y)
    {
        $result = $y;
        return $result;
    }
}

class ConverterPlugin_timex81 extends ConverterPlugin_standard
{
    protected $attributeHeight = 1;

    //todo: remove duplicate
    protected function loadBits()
    {
        $pixelsArray = array();
        $attributesArray = array();
        if (file_exists($this->sourceFilePath) && filesize($this->sourceFilePath) == 12288) {
            $this->handle = fopen($this->sourceFilePath, "rb");

            $length = 0;
            while ($bin = $this->read8BitString()) {
                if ($length < 6144) {
                    $pixelsArray[] = $bin;
                } else {
                    $attributesArray[] = $bin;
                }
                $length++;
            }
            $resultBits = array('pixelsArray' => $pixelsArray, 'attributesArray' => $attributesArray);
            return $resultBits;
        }
        return false;
    }

    protected function parseAttributes($attributesArray)
    {
        $x = 0;
        $y = 0;
        $zxY = 0;
        $attributesData = array('inkMap' => array(), 'paperMap' => array(), 'flashMap' => array());
        foreach ($attributesArray as &$bits) {
            $ink = substr($bits, 1, 1) . substr($bits, 5);
            $paper = substr($bits, 1, 4);

            $attributesData['inkMap'][$zxY][$x] = $ink;
            $attributesData['paperMap'][$zxY][$x] = $paper;

            $flashStatus = substr($bits, 0, 1);
            if ($flashStatus == '1') {
                $attributesData['flashMap'][$y][$x] = $flashStatus;
            }

            if ($x == ($this->width / 8) - 1) {
                $x = 0;
                $y++;
                $zxY = $this->calculateZXY($y);
            } else {
                $x++;
            }
        }
        return $attributesData;
    }

}