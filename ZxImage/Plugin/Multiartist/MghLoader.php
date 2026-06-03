<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

use ZxImage\Dto\PluginInput;
use ZxImage\Service\PluginServices;

final readonly class MghLoader
{
    private const int MGH_MODE_1 = 1;
    private const int MGH_MODE_2 = 2;
    private const int MGH_MODE_4 = 4;
    private const int VERSION_OFFSET = 3;
    private const int MODE_OFFSET = 4;
    private const int FIRST_BORDER_OFFSET = 5;
    private const int SECOND_BORDER_OFFSET = 6;
    private const int HEADER_LENGTH = 256;
    private const int SCREEN_PIXELS_LENGTH = 6144;
    private const int SUPPORTED_VERSION = 1;
    private const string SIGNATURE = 'MGH';

    public function loadFrom(PluginInput $input, PluginServices $services, bool $usesFileBorders): ?MghData
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            null,
        );
        if ($reader === null) {
            return null;
        }

        $header = $reader->readString(self::HEADER_LENGTH);
        if ($header === null || $this->isSupportedHeader($header) === false) {
            return null;
        }

        $mode = ord($header[self::MODE_OFFSET]);
        $dimensions = $this->getDimensions($mode);
        $firstPixelsBytes = $reader->readBytes(self::SCREEN_PIXELS_LENGTH);
        $secondPixelsBytes = $reader->readBytes(self::SCREEN_PIXELS_LENGTH);
        $firstAttributesBytes = $reader->readBytes($dimensions->attributesLength);
        $secondAttributesBytes = $reader->readBytes($dimensions->attributesLength);
        $firstOuterAttributesBytes = [];
        $secondOuterAttributesBytes = [];

        if ($mode === self::MGH_MODE_1) {
            $firstOuterAttributesBytes = $reader->readBytes($dimensions->outerAttributesLength);
            $secondOuterAttributesBytes = $reader->readBytes($dimensions->outerAttributesLength);
        }

        return new MghData(
            $mode,
            $this->parseBorders($header, $usesFileBorders),
            $dimensions,
            $firstPixelsBytes,
            $secondPixelsBytes,
            $firstAttributesBytes,
            $secondAttributesBytes,
            $firstOuterAttributesBytes,
            $secondOuterAttributesBytes,
        );
    }

    private function isSupportedHeader(string $header): bool
    {
        $signature = substr($header, 0, 3);
        $version = ord($header[self::VERSION_OFFSET]);

        return $signature === self::SIGNATURE && $version === self::SUPPORTED_VERSION;
    }

    private function parseBorders(string $header, bool $usesFileBorders): MghBorders
    {
        if ($usesFileBorders === false) {
            return new MghBorders(null, null);
        }

        return new MghBorders(
            ord($header[self::FIRST_BORDER_OFFSET]),
            ord($header[self::SECOND_BORDER_OFFSET]),
        );
    }

    private function getDimensions(int $mode): MghDimensions
    {
        return match ($mode) {
            self::MGH_MODE_1 => new MghDimensions(1, 3072, 384),
            self::MGH_MODE_2 => new MghDimensions(2, 3072, 0),
            self::MGH_MODE_4 => new MghDimensions(4, 1536, 0),
            default => new MghDimensions(8, 768, 0),
        };
    }
}
