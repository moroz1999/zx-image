<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Bmc4;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Service\PluginServices;

final readonly class Bmc4Loader
{
    private const int PIXELS_SIZE = 6144;
    private const int STANDARD_ATTRIBUTES_SIZE = 768;
    private const int ROWS = 24;
    private const int COLS = 32;

    public function loadFrom(
        PluginInput $input,
        PluginGeometry $geometry,
        PluginServices $services,
    ): ?RawScreen
    {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $pixelsBytes = $reader->readBytes(self::PIXELS_SIZE);
        $firstAttributesBytes = $reader->readBytes(self::STANDARD_ATTRIBUTES_SIZE);
        $secondAttributesBytes = $reader->readBytes(self::STANDARD_ATTRIBUTES_SIZE);
        $attributesBytes = $this->interleaveAttributes($firstAttributesBytes, $secondAttributesBytes);

        $borderBytes = [];
        while (($byte = $reader->readByte()) !== null) {
            $borderBytes[] = $byte;
        }

        return new RawScreen($pixelsBytes, $attributesBytes, $borderBytes);
    }

    /**
     * @param list<int> $firstBytes
     * @param list<int> $secondBytes
     *
     * @return list<int>
     */
    private function interleaveAttributes(array $firstBytes, array $secondBytes): array
    {
        $result = [];
        for ($row = 0; $row < self::ROWS; $row++) {
            $offset = $row * self::COLS;
            array_push($result, ...array_slice($firstBytes, $offset, self::COLS));
            array_push($result, ...array_slice($secondBytes, $offset, self::COLS));
        }
        return $result;
    }
}
