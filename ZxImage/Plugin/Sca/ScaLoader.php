<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Sca;

use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RawScreen;
use ZxImage\Dto\RenderSettings;
use ZxImage\Service\BitReader;
use ZxImage\Service\PluginServices;

final readonly class ScaLoader
{
    private const int VERSION = 1;
    private const int PAYLOAD_TYPE_RAW = 0;
    private const int PIXELS_SIZE = 6144;
    private const int ATTRIBUTES_SIZE = 768;
    private const int TICKS_PER_SECOND = 50;
    private const int CENTISECONDS_PER_SECOND = 100;

    public function loadFrom(
        PluginInput $input,
        PluginGeometry $geometry,
        RenderSettings $renderSettings,
        PluginServices $services,
    ): ?ScaData {
        $reader = $services->fileLoader->openSource(
            $input->sourceFilePath,
            $input->sourceFileContents,
            $geometry->requiredFileSize,
        );
        if ($reader === null) {
            return null;
        }

        $signature = $reader->readString(3);
        if ($signature !== 'SCA') {
            return null;
        }

        $version = $reader->readByte();
        if ($version !== self::VERSION) {
            return null;
        }

        $geometry = $geometry->withDimensions(
            $reader->readWord() ?? $geometry->width,
            $reader->readWord() ?? $geometry->height,
        );
        $renderSettings = $renderSettings->withBorder($reader->readByte());
        $framesAmount = $reader->readWord() ?? 0;
        $payloadType = $reader->readByte();
        if ($payloadType !== self::PAYLOAD_TYPE_RAW) {
            return null;
        }

        $dataPointer = $reader->readWord() ?? 0;
        $reader->seek($dataPointer);

        $delays = [];
        for ($i = 0; $i < $framesAmount; $i++) {
            $delays[] = (int)(($reader->readByte() ?? 0) * (self::CENTISECONDS_PER_SECOND / self::TICKS_PER_SECOND));
        }

        return new ScaData(
            $geometry,
            $renderSettings,
            $delays,
            $this->readScreens($reader, $framesAmount),
        );
    }

    /**
     * @return iterable<int, RawScreen>
     */
    private function readScreens(BitReader $reader, int $framesAmount): iterable
    {
        for ($i = 0; $i < $framesAmount; $i++) {
            yield new RawScreen(
                $reader->readBytes(self::PIXELS_SIZE),
                $reader->readBytes(self::ATTRIBUTES_SIZE),
            );
        }
    }
}
