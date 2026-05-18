<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Filter\Filter;

abstract class Plugin implements Configurable
{
    protected ?Converter $converter;
    /*
     * @var resource $handle
     */
    protected $handle;
    protected ?int $strictFileSize;
    protected array $colors = [];
    protected array $gigaColors = [];
    protected ?string $sourceFilePath;
    protected ?string $sourceFileContents;
    protected string $gigascreenMode = 'mix';
    protected array $palette;
    protected ?int $border = null;
    protected float $zoom = 1;
    protected ?string $resultMime = null;

    protected bool $usesBorder = true;

    protected array $preFilters = [];
    protected array $postFilters = [];

    protected int $width = 256;
    protected int $height = 192;

    protected int $attributeWidth = 8;
    protected int $attributeHeight = 8;
    protected int $borderWidth = 32;
    protected int $borderHeight = 24;
    protected int $rotation;
    protected string $basePath;

    public function __construct(
        ?string    $sourceFilePath = null,
        ?string    $sourceFileContents = null,
        ?Converter $converter = null
    )
    {
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * @param Filter[] $filters
     */
    public function setPreFilters(array $filters): void
    {
        $this->preFilters = $filters;
    }

    /**
     * @param Filter[] $filters
     */
    public function setPostFilters(array $filters): void
    {
        $this->postFilters = $filters;
    }

    public function setBorder(?int $border = null): void
    {
        $this->border = $border;
    }

    public function setZoom(float $zoom): void
    {
        $this->zoom = $zoom;
    }

    public function setRotation(int $rotation): void
    {
        $this->rotation = $rotation;
    }

    public function setGigascreenMode(string $mode): void
    {
        if ($mode === 'flicker' || $mode === 'interlace2' || $mode === 'interlace1') {
            $this->gigascreenMode = $mode;
        }
    }

    public function setPalette(string $palette): void
    {
        $this->parsePalette($palette);
        $this->generateColors();
        $this->generateGigaColors();
    }

    protected function parsePalette($palette): void
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

    protected function generateColors(): void
    {
        $palette = $this->palette;
        for ($colorIndex = 0; $colorIndex < 16; $colorIndex++) {
            $bright = ($colorIndex >> 3) & 1;
            $green = ($colorIndex >> 2) & 1;
            $red = ($colorIndex >> 1) & 1;
            $blue = $colorIndex & 1;

            $zero = $palette['ZZ'];
            $one = $bright === 1 ? $palette['BB'] : $palette['NN'];

            $r = (1 - $red) * $zero + $red * $one;
            $g = (1 - $green) * $zero + $green * $one;
            $b = (1 - $blue) * $zero + $blue * $one;

            $redChannel = (int)round(($r * $palette['R11'] + $g * $palette['R12'] + $b * $palette['R13']) / 0xFF);
            $greenChannel = (int)round(($r * $palette['R21'] + $g * $palette['R22'] + $b * $palette['R23']) / 0xFF);
            $blueChannel = (int)round(($r * $palette['R31'] + $g * $palette['R32'] + $b * $palette['R33']) / 0xFF);

            $this->colors[$colorIndex] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
        }
    }

    protected function generateGigaColors(): void
    {
        $palette = $this->palette;
        $palette['BN'] = $palette['NB'];
        $palette['BZ'] = $palette['ZB'];
        $palette['NZ'] = $palette['ZN'];

        for ($index1 = 0; $index1 < 16; $index1++) {
            $bright1 = ($index1 >> 3) & 1;
            $green1 = ($index1 >> 2) & 1;
            $red1 = ($index1 >> 1) & 1;
            $blue1 = $index1 & 1;

            for ($index2 = 0; $index2 < 16; $index2++) {
                $bright2 = ($index2 >> 3) & 1;
                $green2 = ($index2 >> 2) & 1;
                $red2 = ($index2 >> 1) & 1;
                $blue2 = $index2 & 1;

                $r = $palette[$this->gigaChannelLevel($bright1, $red1) . $this->gigaChannelLevel($bright2, $red2)];
                $g = $palette[$this->gigaChannelLevel($bright1, $green1) . $this->gigaChannelLevel($bright2, $green2)];
                $b = $palette[$this->gigaChannelLevel($bright1, $blue1) . $this->gigaChannelLevel($bright2, $blue2)];

                $redChannel = (int)round(($r * $palette['R11'] + $g * $palette['R12'] + $b * $palette['R13']) / 0xFF);
                $greenChannel = (int)round(($r * $palette['R21'] + $g * $palette['R22'] + $b * $palette['R23']) / 0xFF);
                $blueChannel = (int)round(($r * $palette['R31'] + $g * $palette['R32'] + $b * $palette['R33']) / 0xFF);

                $this->gigaColors[($index1 << 4) | $index2] = $redChannel * 0x010000 + $greenChannel * 0x0100 + $blueChannel;
            }
        }
    }

    private function gigaChannelLevel(int $bright, int $bit): string
    {
        if ($bit === 0) {
            return 'Z';
        }
        return $bright === 1 ? 'B' : 'N';
    }

    public function convert(): ?string
    {
        $result = null;
        if ($bits = $this->loadBits()) {
            $parsedData = $this->parseScreen($bits);
            $image = $this->exportData($parsedData, false);
            $result = $this->makePngFromGd($image);
        }
        return $result;
    }

    abstract protected function loadBits(): ?array;

    abstract protected function parseScreen($data);

    abstract protected function exportData($parsedData, bool $flashedImage = false);

    protected function makePngFromGd(GdImage $image): string
    {
        $this->resultMime = 'image/png';
        ob_start();
        imagepng($image);
        return ob_get_clean();
    }

    protected function makeAvifFromGd(GdImage $image): string
    {
        $this->resultMime = 'image/avif';
        ob_start();
        imageavif($image, null, -1);
        return ob_get_clean();
    }

    /**
     * @return mixed
     */
    public function getResultMime(): mixed
    {
        return $this->resultMime;
    }

    protected function makeHandle(): bool
    {
        if (is_file($this->sourceFilePath)) {
            if (!isset($this->strictFileSize)) {
                $this->strictFileSize = filesize($this->sourceFilePath);
            }
            if ($this->strictFileSize === filesize($this->sourceFilePath)) {
                $this->handle = fopen($this->sourceFilePath, "rb");
                return true;
            }
        } elseif ($this->sourceFileContents) {
            if (!isset($this->strictFileSize)) {
                $this->strictFileSize = strlen($this->sourceFileContents);
            }
            $this->handle = fopen('php://memory', 'wb+');
            fwrite($this->handle, $this->sourceFileContents);
            rewind($this->handle);
            return true;
        }
        return false;
    }

    protected function seek(int $offset): void
    {
        if ($this->handle) {
            fseek($this->handle, $offset);
        }
    }

    protected function readByte(): ?int
    {
        $read = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }

        return ord($read);
    }

    protected function readString(int $length): ?string
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

    protected function readWord(): ?int
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
        imagegammacorrect($srcImage, 2.2, 1.0);

        $dstWidth = $srcWidth;
        $dstHeight = $srcHeight;
        if (in_array($this->zoom, [0.25, 0.5, 2, 3, 4])) {
            $dstWidth = (int)($srcWidth * $this->zoom);
            $dstHeight = (int)($srcHeight * $this->zoom);
        }
        $srcImage = $this->applyPreFilters($srcImage);

        if ($this->zoom == 1) {
            $dstImage = $srcImage;
        } else {
            $dstImage = imagecreatetruecolor($dstWidth, $dstHeight);
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);

            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);
        }
        $dstImage = $this->applyPostFilters($srcImage, $dstImage);

        imagegammacorrect($dstImage, 1.0, 2.2);

        return $dstImage;
    }

    /**
     * @param resource $srcImage
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
        return $srcImage;
    }

    /**
     * @param resource $srcImage
     * @param resource $dstImage
     */
    protected function applyPostFilters($srcImage, $dstImage)
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
        return $dstImage;
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
    )
    {
        if ($this->usesBorder && is_numeric($this->border)) {
            $resultImage = imagecreatetruecolor(
                $this->width + $this->borderWidth * 2,
                $this->height + $this->borderHeight * 2
            );
            $color = $this->colors[$this->border];
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
        return ob_get_clean();
    }
}
