<?php

namespace ZxImage;

class Converter
{
    protected $hash = false;
    protected $colors = array();
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
    protected $size = '2';
    protected $rotation = '0';
    protected $cacheFileName;
    protected $cacheEnabled = false;
    protected $resultMime;
    protected $basePath;

    protected $palette = '';
    protected $palette1 = '00,76,CD,E9,FF,9F:FF,00,00;00,FF,00;00,00,FF'; //pulsar
    protected $palette2 = '00,76,CD,E9,FF,9F:D0,00,00;00,E4,00;00,00,FF'; //orthodox
    protected $palette3 = '00,60,A0,E0,FF,A0:FF,00,00;00,FF,00;00,00,FF'; //alone
    protected $palette4 = '4F,A1,DD,F0,FF,BD:39,73,1D;3C,77,1E;46,8C,23'; //electroscale
    protected $palette5 = '00,96,CD,E8,FF,BC:FF,00,00;00,FF,00;00,00,FF'; //srgb

    public function __construct()
    {
        $this->palette = $this->palette1;
        $this->cacheExpirationLimit = 60 * 60 * 24 * 30 * 1; //delete files older than 2 months
        $this->basePath = pathinfo((__FILE__), PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR;

        if (!class_exists('\ZxImage\ConverterPlugin')) {
            $path = $this->basePath . 'ConverterPlugin.php';
            if (file_exists($path)) {
                include_once($path);
            }
        }
    }

    /**
     * @param mixed $sourceFileContents
     */
    public function setSourceFileContents($sourceFileContents)
    {
        $this->sourceFileContents = $sourceFileContents;
    }

    /**
     * @param boolean $cacheEnabled
     */
    public function setCacheEnabled($cacheEnabled)
    {
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * @param mixed $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath . DIRECTORY_SEPARATOR;
        $this->cacheDirMarkerPath = $this->cachePath . DIRECTORY_SEPARATOR . '_marker';
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
        } elseif ($palette == 'srgb') {
            $this->palette = $this->palette5;
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
            if ($size >= 0 && $size <= 6) {
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
            $fileName = 'ConverterPlugin' . DIRECTORY_SEPARATOR . 'multiartist.php';
            $className = '\ZxImage\\ConverterPlugin_multiartist';
        } elseif ($this->type == 'chr$') {
            $fileName = 'ConverterPlugin' . DIRECTORY_SEPARATOR . 'chrd.php';
            $className = '\ZxImage\\ConverterPlugin_chrd';
        } else {
            $fileName = 'ConverterPlugin' . DIRECTORY_SEPARATOR . $this->type . '.php';
            $className = '\ZxImage\\ConverterPlugin_' . $this->type;
        }
        if (!class_exists($className)) {
            $path = $this->basePath . $fileName;
            if (file_exists($path)) {
                include_once($path);
            }
        }
        if (class_exists($className)) {
            /**
             * @var \ZxImage\ConverterPlugin $converter
             */
            $converter = new $className($this->sourceFilePath, $this->sourceFileContents);
            $converter->setBorder($this->border);
            $converter->setPalette($this->palette);
            $converter->setSize($this->size);
            $converter->setRotation($this->rotation);
            $converter->setGigascreenMode($this->gigascreenMode);
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
                array(
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