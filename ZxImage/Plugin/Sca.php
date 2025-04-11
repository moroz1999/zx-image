<?php
declare(strict_types=1);

namespace ZxImage\Plugin;


use GifCreator\GifCreator;

class Sca extends Standard
{
    private $delays = [];

    public function convert(): ?string
    {
        $result = null;
        if ($frames = $this->loadBits()) {
            $gifImages = [];

            foreach ($frames as $frame) {
                $parsedData = $this->parseScreen($frame);

                $image = $this->exportData($parsedData, false);
                $gifImages[] = $this->getRightPaletteGif($image);
            }
            $delays = $this->delays;
            $result = $this->buildAnimatedGif($gifImages, $delays);
        }
        return $result;
    }

    protected function loadBits(): ?array
    {
        if ($this->makeHandle()) {
            $frames = [];
            $signature = $this->readString(3);
            if ($signature === 'SCA') {
                $version = $this->readByte();
                if ($version !== 1) {
                    return null;
                }
                $this->width = $this->readWord();
                $this->height = $this->readWord();
                $this->border = $this->readByte();
                $framesAmount = $this->readWord();
                $payloadType = $this->readByte();
                if ($payloadType !== 0) {
                    return null;
                }
                $dataPointer = $this->readWord();
                $this->seek($dataPointer);

                for ($i = 0; $i < $framesAmount; $i++) {
                    $this->delays[] = (int)($this->readByte() * (50 / 1000));
                }

                for ($i = 0; $i < $framesAmount; $i++) {
                    $pixelsArray = $this->read8BitStrings(6144);
                    $attributesArray = $this->read8BitStrings(768);
                    $frames[] = compact('pixelsArray', 'attributesArray');
                }
            }
            return $frames;
        }
        return null;
    }

}
