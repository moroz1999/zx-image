<?php

namespace ZxImage;

use ZxImage\Plugin\Plugin;

class Converter
{
    protected ?string $hash = null;
    protected array $colors = [];
    protected string $gigascreenMode = 'mix';
    protected string $cachePath;
    protected ?string $sourceFileContents = null;
    protected string $sourceFilePath;
    protected string $resultFilePath;
    protected string $cacheDirMarkerPath;
    protected int $cacheDeletionPeriod = 300; //start cache clearing every 5 minutes
    protected int $cacheDeletionAmount = 1000; //delete not more than 1000 images at once
    protected int $cacheExpirationLimit;
    protected string $type = 'standard';
    protected ?int $border = null;
    protected float $zoom = 1;
    protected int $rotation = 0;
    protected string $cacheFileName;
    protected bool $cacheEnabled = false;
    protected ?string $resultMime = null;
    protected string $basePath;
    protected array $preFilters = [];
    protected array $postFilters = [];

    protected string $palette = '';
    protected string $palette1 = '00,76,CD,E9,FF,9F:FF,00,00;00,FF,00;00,00,FF'; //pulsar
    protected string $palette2 = '00,76,CD,E9,FF,9F:D0,00,00;00,E4,00;00,00,FF'; //orthodox
    protected string $palette3 = '00,60,A0,E0,FF,A0:FF,00,00;00,FF,00;00,00,FF'; //alone
    protected string $palette4 = '4F,A1,DD,F0,FF,BD:39,73,1D;3C,77,1E;46,8C,23'; //electroscale
    protected string $palette5 = '00,96,CD,E8,FF,BC:FF,00,00;00,FF,00;00,00,FF'; //srgb

    public function __construct()
    {
        $this->palette = $this->palette5;
        $this->cacheExpirationLimit = 60 * 60 * 24 * 30 * 1; //delete files older than 2 months
        $this->basePath = pathinfo((__FILE__), PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
    }

    public function setSourceFileContents(string $sourceFileContents): Converter
    {
        $this->sourceFileContents = $sourceFileContents;
        return $this;
    }

    public function setCacheEnabled(bool $cacheEnabled): Converter
    {
        $this->cacheEnabled = $cacheEnabled;
        return $this;
    }

    public function setCachePath(string $cachePath): Converter
    {
        $this->cachePath = $cachePath . DIRECTORY_SEPARATOR;
        $this->cacheDirMarkerPath = $this->cachePath . DIRECTORY_SEPARATOR . '_marker';
        return $this;
    }

    public function setCacheExpirationLimit(int $cacheExpirationLimit): Converter
    {
        $this->cacheExpirationLimit = $cacheExpirationLimit;
        return $this;
    }

    public function setCacheDeletionAmount(int $cacheDeletionAmount): Converter
    {
        $this->cacheDeletionAmount = $cacheDeletionAmount;
        return $this;
    }

    public function setCacheDeletionPeriod(int $cacheDeletionPeriod): Converter
    {
        $this->cacheDeletionPeriod = $cacheDeletionPeriod;
        return $this;
    }

    public function setGigascreenMode(string $mode): Converter
    {
        if ($mode === 'flicker' || $mode === 'interlace2' || $mode === 'interlace1' || $mode === 'mix') {
            $this->gigascreenMode = $mode;
        }
        return $this;
    }

    public function setRotation(int $rotation): Converter
    {
        if (in_array($rotation, [0, 90, 180, 270])) {
            $this->rotation = $rotation;
        }
        return $this;
    }

    public function addPreFilter(string $type): Converter
    {
        $this->preFilters[] = $type;
        return $this;
    }

    public function addPostFilter(string $type): Converter
    {
        $this->postFilters[] = $type;
        return $this;
    }

    public function setPalette(string $palette): Converter
    {
        if ($palette == 'orthodox') {
            $this->palette = $this->palette2;
        } elseif ($palette == 'alone') {
            $this->palette = $this->palette3;
        } elseif ($palette == 'electroscale') {
            $this->palette = $this->palette4;
        } elseif ($palette == 'srgb') {
            $this->palette = $this->palette5;
        } elseif ($palette == 'pulsar') {
            $this->palette = $this->palette1;
        } else {
            $this->palette = $this->palette5;
        }
        return $this;
    }

    public function setBorder(int $border = null): Converter
    {
        if ($border >= 0 && $border < 8 || $border === null) {
            $this->border = $border;
        }
        return $this;
    }

    /**
     * @param $zoom
     * @return $this
     */
    public function setZoom(float $zoom): Converter
    {
        if (is_numeric($zoom)) {
            $zoom = floatval($zoom);
            if ($zoom >= 0.25 && $zoom <= 3) {
                $this->zoom = $zoom;
            }
        }
        return $this;
    }

    public function setType(string $type): Converter
    {
        $this->type = $type;
        return $this;
    }

    public function setPath(string $path): Converter
    {
        $this->sourceFilePath = $path;
        return $this;
    }

    public function getResultMime(): ?string
    {
        $resultMime = null;
        if ($this->resultMime) {
            $resultMime = $this->resultMime;
        } elseif ($this->cacheEnabled) {
            if ($resultFilePath = $this->getCacheFileName()) {
                if (is_file($resultFilePath) && ($info = getimagesize($resultFilePath))) {
                    $resultMime = $info['mime'];
                }
            }
        }
        return $resultMime;
    }

    public function getCacheFileName(): string
    {
        $parametersHash = $this->getHash();

        $this->cacheFileName = $this->cachePath . $parametersHash;
        return $this->cacheFileName;
    }

    public function getHash(): ?string
    {
        if (!$this->hash && ($this->sourceFileContents || is_file($this->sourceFilePath))) {
            $text = '';
            if (is_file($this->sourceFilePath)) {
                $text .= $this->sourceFilePath;
                $text .= filemtime($this->sourceFilePath);
            } elseif ($this->sourceFileContents) {
                $text .= md5($this->sourceFileContents);
            }
            $text .= $this->type;
            if (in_array(
                $this->type,
                [
                    'gigascreen',
                    'tricolor',
                    'multiartist',
                    'mg1',
                    'mg2',
                    'mg4',
                    'mg8',
                    'lowresgs',
                    'chr$',
                    'bsp',
                    'timexhrg',
                    'stellar',
                ]
            )
            ) {
                $text .= $this->gigascreenMode;
            }
            $text .= $this->border;
            $text .= $this->palette;
            $text .= $this->zoom;
            $text .= implode($this->preFilters);
            $text .= implode($this->postFilters);
            if ($this->rotation > 0) {
                $text .= $this->rotation;
            }

            $this->hash = md5($text);
        }
        return $this->hash;
    }

    public function getBinary(): ?string
    {
        if (!$this->cacheEnabled) {
            return $this->generateBinary();
        } else {
            return $this->generateCacheFile();
        }
    }

    public function generateBinary(): ?string
    {
        $result = null;
        if ($this->type == 'mg1' || $this->type == 'mg2' || $this->type == 'mg4' || $this->type == 'mg8') {
            $className = 'multiartist';
        } elseif ($this->type == 'chr$') {
            $className = 'chrd';
        } else {
            $className = '' . $this->type;
        }
        $className = __NAMESPACE__ . '\\Plugin\\' . ucfirst($className);
        if (class_exists($className)) {
            /**
             * @var Plugin $converter
             */
            $converter = new $className($this->sourceFilePath, $this->sourceFileContents, $this);
            $converter->setBasePath($this->basePath);
            $converter->setBorder($this->border);
            $converter->setPalette($this->palette);
            $converter->setZoom($this->zoom);
            $converter->setRotation($this->rotation);
            $converter->setGigascreenMode($this->gigascreenMode);
            $converter->setPreFilters($this->preFilters);
            $converter->setPostFilters($this->postFilters);
            if ($result = $converter->convert()) {
                $this->resultMime = $converter->getResultMime();
            }
        }
        return $result;
    }

    public function generateCacheFile(): ?string
    {
        $result = null;
        if ($resultFilePath = $this->getCacheFileName()) {
            if (!file_exists($resultFilePath)) {
                if ($result = $this->generateBinary()) {
                    file_put_contents($resultFilePath, $result);
                }
            } else {
                $result = file_get_contents($resultFilePath);
            }

            $this->checkCacheClearing();
        }
        return $result;
    }

    protected function checkCacheClearing(): void
    {
        if ($date = $this->getCacheLastClearedDate()) {
            $now = time();
            if ($now - $date >= $this->cacheDeletionPeriod) {
                touch($this->cacheDirMarkerPath);
                $this->clearOutdatedCache();
            }
        }
    }

    protected function getCacheLastClearedDate(): ?int
    {
        $date = null;

        if (!is_file($this->cacheDirMarkerPath)) {
            file_put_contents($this->cacheDirMarkerPath, ' ');
            return 1;
        }
        if (is_file($this->cacheDirMarkerPath)) {
            $date = filemtime($this->cacheDirMarkerPath);
        }
        return $date;
    }

    protected function clearOutdatedCache(): void
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
    }
}