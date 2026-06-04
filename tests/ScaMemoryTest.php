<?php

declare(strict_types=1);

namespace ZxImage\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use ZxImage\Converter;

final class ScaMemoryTest extends TestCase
{
    private const string SIGNATURE = 'SCA';
    private const int VERSION = 1;
    private const int FRAME_COUNT = 285;
    private const int WIDTH = 256;
    private const int HEIGHT = 192;
    private const int BORDER = 0;
    private const int PAYLOAD_TYPE_RAW = 0;
    private const int DATA_POINTER = 14;
    private const int FRAME_DELAY = 2;
    private const int PIXELS_SIZE = 6144;
    private const int EMPTY_PIXEL_BYTE = 0;
    private const int ATTRIBUTES_SIZE = 768;
    private const int PAPER_WHITE_ATTRIBUTE = 7;
    private const string MEMORY_LIMIT = '128M';
    private const int ZOOMED_MAX_RSS_KILOBYTES = 98304;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLongAnimationIsRenderedWithinMemoryLimit(): void
    {
        ini_set('memory_limit', self::MEMORY_LIMIT);
        self::assertSame(self::MEMORY_LIMIT, ini_get('memory_limit'));

        $binary = (new Converter())
            ->setType('sca')
            ->setSourceFileContents($this->createScaSource())
            ->getBinary();

        self::assertIsString($binary);
        self::assertStringStartsWith('GIF89a', $binary);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLongZoomedAnimationDoesNotRetainFinalizedFrames(): void
    {
        $binary = (new Converter())
            ->setType('sca')
            ->setSourceFileContents($this->createScaSource())
            ->setZoom(3)
            ->getBinary();

        self::assertIsString($binary);
        self::assertStringStartsWith('GIF89a', $binary);
        self::assertLessThan(self::ZOOMED_MAX_RSS_KILOBYTES, getrusage()['ru_maxrss']);
    }

    private function createScaSource(): string
    {
        $header = self::SIGNATURE
            . chr(self::VERSION)
            . pack('v', self::WIDTH)
            . pack('v', self::HEIGHT)
            . chr(self::BORDER)
            . pack('v', self::FRAME_COUNT)
            . chr(self::PAYLOAD_TYPE_RAW)
            . pack('v', self::DATA_POINTER);
        $delays = str_repeat(chr(self::FRAME_DELAY), self::FRAME_COUNT);
        $frame = str_repeat(chr(self::EMPTY_PIXEL_BYTE), self::PIXELS_SIZE)
            . str_repeat(chr(self::PAPER_WHITE_ATTRIBUTE), self::ATTRIBUTES_SIZE);

        return $header . $delays . str_repeat($frame, self::FRAME_COUNT);
    }
}
