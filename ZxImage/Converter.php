<?php

namespace ZxImage;

use ZxImage\Plugin\Plugin;

class Converter
{
    protected $hash = false;
    protected $colors = [];
    protected $gigascreenMode = 'mix';
    protected $cachePath;
    protected $sourceFileContents;
    protected $sourceFilePath;
    protected $resultFilePath;
    protected $cacheDirMarkerPath;
    protected $cacheDeletionPeriod = 300; //start cache clearing every 5 minutes
    protected $cacheDeletionAmount = 1000; //delete not more than 1000 images at once
    protected $cacheExpirationLimit = false;
    protected $type = 'standard';
    protected $border = false;
    protected $zoom = 1;
    protected $rotation = '0';
    protected $cacheFileName;
    protected $cacheEnabled = false;
    protected $resultMime;
    protected $basePath;
    protected $preFilters = [];
    protected $postFilters = [];

    protected $palette = '';
    protected $palette1 = '00,76,CD,E9,FF,9F:FF,00,00;00,FF,00;00,00,FF'; //pulsar
    protected $palette2 = '00,76,CD,E9,FF,9F:D0,00,00;00,E4,00;00,00,FF'; //orthodox
    protected $palette3 = '00,60,A0,E0,FF,A0:FF,00,00;00,FF,00;00,00,FF'; //alone
    protected $palette4 = '4F,A1,DD,F0,FF,BD:39,73,1D;3C,77,1E;46,8C,23'; //electroscale
    protected $palette5 = '00,96,CD,E8,FF,BC:FF,00,00;00,FF,00;00,00,FF'; //srgb

    public function __construct()
    {
        $this->palette = $this->palette5;
        $this->cacheExpirationLimit = 60 * 60 * 24 * 30 * 1; //delete files older than 2 months
        $this->basePath = pathinfo((__FILE__), PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param mixed $sourceFileContents
     * @return Converter
     */
    public function setSourceFileContents($sourceFileContents)
    {
        $this->sourceFileContents = $sourceFileContents;
        return $this;
    }

    /**
     * @param boolean $cacheEnabled
     * @return Converter
     */
    public function setCacheEnabled($cacheEnabled)
    {
        $this->cacheEnabled = $cacheEnabled;
        return $this;
    }

    /**
     * @param mixed $cachePath
     * @return Converter
     */
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath . DIRECTORY_SEPARATOR;
        $this->cacheDirMarkerPath = $this->cachePath . DIRECTORY_SEPARATOR . '_marker';
        return $this;
    }

    /**
     * @param bool|int $cacheExpirationLimit
     * @return Converter
     */
    public function setCacheExpirationLimit($cacheExpirationLimit)
    {
        $this->cacheExpirationLimit = $cacheExpirationLimit;
        return $this;
    }

    /**
     * @param int $cacheDeletionAmount
     * @return Converter
     */
    public function setCacheDeletionAmount($cacheDeletionAmount)
    {
        $this->cacheDeletionAmount = $cacheDeletionAmount;
        return $this;
    }

    /**
     * @param int $cacheDeletionPeriod
     * @return Converter
     */
    public function setCacheDeletionPeriod($cacheDeletionPeriod)
    {
        $this->cacheDeletionPeriod = $cacheDeletionPeriod;
        return $this;
    }

    public function setGigascreenMode($mode)
    {
        if ($mode == 'flicker' || $mode == 'interlace2' || $mode == 'interlace1' || $mode == 'mix') {
            $this->gigascreenMode = $mode;
        }
        return $this;
    }

    public function setRotation($rotation)
    {
        if (in_array($rotation, [0, 90, 180, 270])) {
            $this->rotation = $rotation;
        }
        return $this;
    }

    public function addPreFilter($type)
    {
        $this->preFilters[] = $type;
        return $this;
    }

    public function addPostFilter($type)
    {
        $this->postFilters[] = $type;
        return $this;
    }

    public function setPalette($palette)
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

    public function setBorder($border)
    {
        if ($border >= 0 && $border < 8 || $border === false) {
            $this->border = $border;
        }
        return $this;
    }

    /**
     * @param $size
     * @return $this
     *
     * @deprecated
     */
    public function setSize($size)
    {
        if (is_numeric($size)) {
            $size = intval($size);
            if ($size >= 0 && $size <= 7) {
                switch ($size) {
                    case 0:
                    case 1:
                        $this->setZoom(0.25);
                        break;
                    case 2:
                        $this->setZoom(1);
                        break;
                    case 3:
                        $this->setZoom(2);
                        $this->addPostFilter('scanlines');
                        break;
                    case 4:
                        $this->setZoom(2);
                        break;
                    case 5:
                        $this->setZoom(2);
                        $this->addPostFilter('blur');
                        break;
                    case 6:
                        $this->setZoom(1);
                        $this->addPreFilter('atari');
                        break;
                    case 7:
                        $this->setZoom(3);
                        break;
                }
            }
        }
        return $this;
    }

    /**
     * @param $zoom
     * @return $this
     */
    public function setZoom($zoom)
    {
        if (is_numeric($zoom)) {
            $zoom = floatval($zoom);
            if ($zoom >= 0.25 && $zoom <= 3) {
                $this->zoom = $zoom;
            }
        }
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setPath($path)
    {
        $this->sourceFilePath = $path;
        return $this;
    }

    public function getResultMime()
    {
        $resultMime = false;
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

    public function getCacheFileName()
    {
        $parametersHash = $this->getHash();

        $this->cacheFileName = $this->cachePath . $parametersHash;
        return $this->cacheFileName;
    }

    public function getBinary()
    {
        if (!$this->cacheEnabled) {
            return $this->generateBinary();
        } else {
            return $this->generateCacheFile();
        }
    }

    public function generateCacheFile()
    {
        $result = false;
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

    public function generateBinary()
    {
        $result = false;
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

    public function getHash()
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