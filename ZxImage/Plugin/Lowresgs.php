<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use ZxImage\Converter;
use ZxImage\Dto\DualRawScreen;
use ZxImage\Dto\RawScreen;

class Lowresgs implements PluginInterface
{
    use GigascreenConvertTrait;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->requiredFileSize = 1628;
        $this->sourceFilePath = $sourceFilePath;
        $this->sourceFileContents = $sourceFileContents;
        $this->converter = $converter;
        $this->initServices();
    }

    protected function loadBits(): ?DualRawScreen
    {
        $reader = $this->fileLoader->openSource($this->sourceFilePath, $this->sourceFileContents, $this->requiredFileSize);
        if ($reader === null) {
            return null;
        }

        $texture = [];
        $attr0 = [];
        $attr1 = [];
        $length = 0;
        while (($bin = $reader->readByte()) !== null) {
            if ($length >= 84 && $length < 92) {
                $texture[] = $bin;
            } elseif ($length >= 92 && $length < 92 + 768) {
                $attr0[] = $bin;
            } elseif ($length >= 92 + 768) {
                $attr1[] = $bin;
            }
            $length++;
        }

        $pixelsArray = $this->generatePixelsArray($texture);
        return new DualRawScreen(
            new RawScreen($pixelsArray, $attr0),
            new RawScreen($pixelsArray, $attr1),
        );
    }

    private function generatePixelsArray(array $texture): array
    {
        $pixelsArray = [];
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
