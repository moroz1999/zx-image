<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Bsp;

use ZxImage\Dto\BspData;
use ZxImage\Dto\ParsedScreen;
use ZxImage\Plugin\Standard\AttributeParser;
use ZxImage\Plugin\Standard\PixelParser;
use ZxImage\Service\PluginRuntime;

final readonly class BspLoader
{
    private const int HEADER_SIZE = 70;
    private const int OFFSET_WORD_SIZE = 2;
    private const int SCREEN_SIZE = 6912;

    public function load(PluginRuntime $runtime): ?BspData
    {
        $reader = $runtime->fileLoader->openSource($runtime->sourceFilePath, $runtime->sourceFileContents, null);
        if ($reader === null) {
            return null;
        }

        $signature = $reader->readString(3);
        if ($signature !== 'bsp') {
            return null;
        }

        $configByte = $reader->readByte();
        if ($configByte === null) {
            return null;
        }

        $hasGigaData = (bool)($configByte & 0b10000000);
        $hasBorderData = (bool)($configByte & 0b01000000);

        $reader->readByte();
        $borderColor = $reader->readByte();
        $reader->readString(32);
        $reader->readString(32);

        $border1 = 0;
        $border2 = 0;
        if (!$hasBorderData && $borderColor !== null) {
            $border1 = $borderColor & 0x07;
            $border2 = ($borderColor >> 3) & 0x07;
        }

        $fileSize = $reader->getSize();

        $secondBorderDataOffset = 0;
        if ($hasBorderData && $hasGigaData) {
            $secondBorderDataOffset = $reader->readWord() ?? 0;
        }

        $pixelsBytes1 = $reader->readBytes(6144);
        $attributesBytes1 = $reader->readBytes(768);

        $pixelsBytes2 = [];
        $attributesBytes2 = [];
        if ($hasGigaData) {
            $pixelsBytes2 = $reader->readBytes(6144);
            $attributesBytes2 = $reader->readBytes(768);
        }

        $borderParser = new BspBorderParser();
        $borderData1 = [];
        $borderData2 = [];
        if ($hasBorderData) {
            if ($hasGigaData) {
                $firstBorderLength = $secondBorderDataOffset - self::SCREEN_SIZE * 2 - self::HEADER_SIZE - self::OFFSET_WORD_SIZE;
                $secondBorderLength = $fileSize - $secondBorderDataOffset;
                $borderData1 = $borderParser->parse($reader->readBytes($firstBorderLength), $runtime->width, $runtime->height, $runtime->borderWidth, $runtime->borderHeight);
                $borderData2 = $borderParser->parse($reader->readBytes($secondBorderLength), $runtime->width, $runtime->height, $runtime->borderWidth, $runtime->borderHeight);
            } else {
                $firstBorderLength = $fileSize - self::SCREEN_SIZE - self::HEADER_SIZE;
                $borderData1 = $borderParser->parse($reader->readBytes($firstBorderLength), $runtime->width, $runtime->height, $runtime->borderWidth, $runtime->borderHeight);
            }
        }

        $attrParser = new AttributeParser($runtime->width);
        $pixelParser = new PixelParser($runtime->width);

        $screen1 = new ParsedScreen(
            $pixelParser->parse($pixelsBytes1),
            $attrParser->parse($attributesBytes1),
            [],
            $borderData1,
        );

        $screen2 = $hasGigaData
            ? new ParsedScreen(
                $pixelParser->parse($pixelsBytes2),
                $attrParser->parse($attributesBytes2),
                [],
                $borderData2,
            )
            : $screen1;

        return new BspData($hasGigaData, $hasBorderData, $border1, $border2, $screen1, $screen2);
    }
}
