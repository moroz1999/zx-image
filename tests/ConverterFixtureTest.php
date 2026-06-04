<?php

declare(strict_types=1);

namespace ZxImage\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ZxImage\Converter;

final class ConverterFixtureTest extends TestCase
{
    private const string EXAMPLE_PATH = __DIR__ . '/../example/';
    private const string EXPECTED_PATH = __DIR__ . '/fixtures/expected/';
    private const string RECEIVED_PATH = __DIR__ . '/fixtures/received/';

    #[DataProvider('formatFixtures')]
    public function testFormatConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $this->assertConversionMatchesExpectedFixture($fixture);
    }

    #[DataProvider('paletteFixtures')]
    public function testPaletteConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $this->assertConversionMatchesExpectedFixture($fixture);
    }

    #[DataProvider('borderFixtures')]
    public function testBorderConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $this->assertConversionMatchesExpectedFixture($fixture);
    }

    #[DataProvider('mixingFixtures')]
    public function testMixingConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $this->assertConversionMatchesExpectedFixture($fixture);
    }

    #[DataProvider('sizeFixtures')]
    public function testSizeConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $this->assertConversionMatchesExpectedFixture($fixture);
    }

    #[DataProvider('rotationFixtures')]
    public function testRotationConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $this->assertConversionMatchesExpectedFixture($fixture);
    }

    #[DataProvider('filterFixtures')]
    public function testFilterConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $this->assertConversionMatchesExpectedFixture($fixture);
    }

    public function testDisabledCacheDoesNotCreateCacheFile(): void
    {
        $cacheFilePath = $this->createTemporaryCacheFilePath('disabled');

        try {
            $converter = (new Converter())
                ->setType('standard')
                ->setPath(self::EXAMPLE_PATH . 'example.scr');
            $converter->setCacheFileName($cacheFilePath);
            $actualBinary = $converter->getBinary();

            self::assertIsString($actualBinary);
            self::assertFileDoesNotExist($cacheFilePath);
        } finally {
            $this->removeTemporaryCacheFile($cacheFilePath);
        }
    }

    public function testEnabledCacheCreatesAndReadsCacheFile(): void
    {
        $cacheFilePath = $this->createTemporaryCacheFilePath('enabled');

        try {
            $converter = (new Converter())
                ->setType('standard')
                ->setPath(self::EXAMPLE_PATH . 'example.scr')
                ->setCacheEnabled(true);
            $converter->setCacheFileName($cacheFilePath);
            $actualBinary = $converter->getBinary();

            self::assertIsString($actualBinary);
            self::assertFileExists($cacheFilePath);
            self::assertSame($actualBinary, file_get_contents($cacheFilePath));
            self::assertSame('image/png', $converter->getResultMime());

            $cachedBinary = 'cached-binary';
            file_put_contents($cacheFilePath, $cachedBinary);

            $secondConverter = (new Converter())
                ->setType('missing')
                ->setPath(self::EXAMPLE_PATH . 'example.scr')
                ->setCacheEnabled(true);
            $secondConverter->setCacheFileName($cacheFilePath);
            $secondBinary = $secondConverter->getBinary();

            self::assertSame($cachedBinary, $secondBinary);
        } finally {
            $this->removeTemporaryCacheFile($cacheFilePath);
        }
    }

    public function testChangingRenderSettingsInvalidatesHashAndGeneratedCacheFileName(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/zx-image-cache-test-' . bin2hex(random_bytes(8));
        mkdir($cacheDirectory);

        try {
            $converter = (new Converter())
                ->setType('standard')
                ->setPath(self::EXAMPLE_PATH . 'example.scr')
                ->setCachePath($cacheDirectory);

            $initialHash = $converter->getHash();
            $initialCacheFileName = $converter->getCacheFileName();

            $converter->setZoom(2);

            self::assertNotSame($initialHash, $converter->getHash());
            self::assertNotSame($initialCacheFileName, $converter->getCacheFileName());
        } finally {
            rmdir($cacheDirectory);
        }
    }

    public function testChangingConfigurationInvalidatesResultMime(): void
    {
        $converter = (new Converter())
            ->setType('standard')
            ->setPath(self::EXAMPLE_PATH . 'example.scr');

        self::assertIsString($converter->getBinary());
        self::assertSame('image/png', $converter->getResultMime());

        $converter->setType('missing');

        self::assertNull($converter->getResultMime());
    }

    public function testGettingResultMimeDoesNotTriggerConversion(): void
    {
        $converter = (new Converter())
            ->setType('standard')
            ->setPath(self::EXAMPLE_PATH . 'example.scr');

        self::assertNull($converter->getResultMime());
    }

    public function testHashReflectsSourceFileModification(): void
    {
        $sourceFilePath = tempnam(sys_get_temp_dir(), 'zx-image-source-');
        self::assertIsString($sourceFilePath);

        try {
            file_put_contents($sourceFilePath, 'initial');
            touch($sourceFilePath, 100);
            $converter = (new Converter())->setPath($sourceFilePath);
            $initialHash = $converter->getHash();

            touch($sourceFilePath, 200);
            clearstatcache(true, $sourceFilePath);

            self::assertNotSame($initialHash, $converter->getHash());
        } finally {
            unlink($sourceFilePath);
        }
    }

    /**
     * @return iterable<string, array{ConversionFixture}>
     */
    public static function formatFixtures(): iterable
    {
        yield 'standard' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'standard.png')];
        yield 'ulaplus' => [new ConversionFixture(type: 'ulaplus', sourceFileName: 'ulaplus.scr', expectedFileName: 'ulaplus.png')];
        yield 'flash' => [new ConversionFixture(type: 'flash', sourceFileName: 'hwflash.scr', expectedFileName: 'flash.png')];
        yield 'monochrome' => [new ConversionFixture(type: 'monochrome', sourceFileName: 'monochrome.scr', expectedFileName: 'monochrome.png')];
        yield 'tricolor' => [new ConversionFixture(type: 'tricolor', sourceFileName: 'example.3', expectedFileName: 'tricolor.png')];
        yield 'multicolor' => [new ConversionFixture(type: 'multicolor', sourceFileName: 'example.ifl', expectedFileName: 'multicolor.png')];
        yield 'multicolor4' => [new ConversionFixture(type: 'multicolor4', sourceFileName: 'example.mc4', expectedFileName: 'multicolor4.png')];
        yield 'mlt' => [new ConversionFixture(type: 'mlt', sourceFileName: 'example.mlt', expectedFileName: 'mlt.png')];
        yield 'mc' => [new ConversionFixture(type: 'mc', sourceFileName: 'example.mc', expectedFileName: 'mc.png')];
        yield 'bsc' => [new ConversionFixture(type: 'bsc', sourceFileName: 'example.bsc', expectedFileName: 'bsc.png', border: 1)];
        yield 'bsp' => [new ConversionFixture(type: 'bsp', sourceFileName: 'example.bsp', expectedFileName: 'bsp.png', border: 1)];
        yield 'bmc4' => [new ConversionFixture(type: 'bmc4', sourceFileName: 'example.bmc4', expectedFileName: 'bmc4.png', border: 1)];
        yield 'gigascreen' => [new ConversionFixture(type: 'gigascreen', sourceFileName: 'example.img', expectedFileName: 'gigascreen.png')];
        yield 'mg1' => [new ConversionFixture(type: 'mg1', sourceFileName: 'example.mg1', expectedFileName: 'mg1.png')];
        yield 'mg2' => [new ConversionFixture(type: 'mg2', sourceFileName: 'example.mg2', expectedFileName: 'mg2.png')];
        yield 'mg4' => [new ConversionFixture(type: 'mg4', sourceFileName: 'example.mg4', expectedFileName: 'mg4.png')];
        yield 'mg8' => [new ConversionFixture(type: 'mg8', sourceFileName: 'example.mg8', expectedFileName: 'mg8.png')];
        yield 'attributes' => [new ConversionFixture(type: 'attributes', sourceFileName: 'example.atr', expectedFileName: 'attributes.png')];
        yield 'lowresgs' => [new ConversionFixture(type: 'lowresgs', sourceFileName: 'example.hlr', expectedFileName: 'lowresgs.png')];
        yield 'stellar' => [new ConversionFixture(type: 'stellar', sourceFileName: 'example.stl', expectedFileName: 'stellar.png')];
        yield 'chrd' => [new ConversionFixture(type: 'chrd', sourceFileName: 'example.ch$', expectedFileName: 'chrd.png')];
        yield 'nxi' => [new ConversionFixture(type: 'nxi', sourceFileName: 'example.nxi', expectedFileName: 'nxi.png')];
        yield 'sl2' => [new ConversionFixture(type: 'sl2', sourceFileName: 'example.sl2', expectedFileName: 'sl2.png')];
        yield 'zxevo' => [new ConversionFixture(type: 'zxevo', sourceFileName: 'example.bmp', expectedFileName: 'zxevo.png')];
        yield 'sxg' => [new ConversionFixture(type: 'sxg', sourceFileName: 'example.sxg', expectedFileName: 'sxg.png')];
        yield 'sam3' => [new ConversionFixture(type: 'sam3', sourceFileName: 'sam.ss3', expectedFileName: 'sam3.png')];
        yield 'sam4' => [new ConversionFixture(type: 'sam4', sourceFileName: 'sam.ss4', expectedFileName: 'sam4.png')];
        yield 'ssx mode1' => [new ConversionFixture(type: 'ssx', sourceFileName: 'mode1.ssx', expectedFileName: 'ssx-mode1.png')];
        yield 'ssx mode2' => [new ConversionFixture(type: 'ssx', sourceFileName: 'mode2.ssx', expectedFileName: 'ssx-mode2.png')];
        yield 'ssx mode3' => [new ConversionFixture(type: 'ssx', sourceFileName: 'mode3.ssx', expectedFileName: 'ssx-mode3.png')];
        yield 'ssx mode4' => [new ConversionFixture(type: 'ssx', sourceFileName: 'mode4.ssx', expectedFileName: 'ssx-mode4.png')];
        yield 'ssx raw' => [new ConversionFixture(type: 'ssx', sourceFileName: 'raw.ssx', expectedFileName: 'ssx-raw.png')];
        yield 'ssx raw run' => [new ConversionFixture(type: 'ssx', sourceFileName: '0028503-run-1.ssx', expectedFileName: 'ssx-raw-run.png')];
        yield 'timex81' => [new ConversionFixture(type: 'timex81', sourceFileName: 'timex81.scr', expectedFileName: 'timex81.png')];
        yield 'timexhr' => [new ConversionFixture(type: 'timexhr', sourceFileName: 'timexhr.scr', expectedFileName: 'timexhr.png')];
        yield 'timexhrg' => [new ConversionFixture(type: 'timexhrg', sourceFileName: 'example.hrg', expectedFileName: 'timexhrg.png')];
        yield 'grf' => [new ConversionFixture(type: 'grf', sourceFileName: 'example.grf', expectedFileName: 'grf.png')];
        yield 'specscii' => [new ConversionFixture(type: 'specscii', sourceFileName: 'specscii.C', expectedFileName: 'specscii.gif')];
        yield 's80' => [new ConversionFixture(type: 's80', sourceFileName: 'example.s80', expectedFileName: 's80.png')];
        yield 's81' => [new ConversionFixture(type: 's81', sourceFileName: 'example.s81', expectedFileName: 's81.png')];
        yield 'sca' => [new ConversionFixture(type: 'sca', sourceFileName: 'example.sca', expectedFileName: 'sca.gif')];
    }

    /**
     * @return iterable<string, array{ConversionFixture}>
     */
    public static function paletteFixtures(): iterable
    {
        yield 'srgb' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'palette-srgb.png', palette: 'srgb')];
        yield 'pulsar' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'palette-pulsar.png', palette: 'pulsar')];
        yield 'orthodox' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'palette-orthodox.png', palette: 'orthodox')];
        yield 'alone' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'palette-alone.png', palette: 'alone')];
        yield 'electroscale' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'palette-electroscale.png', palette: 'electroscale')];
    }

    /**
     * @return iterable<string, array{ConversionFixture}>
     */
    public static function borderFixtures(): iterable
    {
        yield '0' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-0.png', border: 0)];
        yield '1' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-1.png', border: 1)];
        yield '2' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-2.png', border: 2)];
        yield '3' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-3.png', border: 3)];
        yield '4' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-4.png', border: 4)];
        yield '5' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-5.png', border: 5)];
        yield '6' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-6.png', border: 6)];
        yield '7' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'border-7.png', border: 7)];
    }

    /**
     * @return iterable<string, array{ConversionFixture}>
     */
    public static function mixingFixtures(): iterable
    {
        yield 'mix' => [new ConversionFixture(type: 'gigascreen', sourceFileName: 'example.img', expectedFileName: 'gigascreen-mix.png', gigascreenMode: 'mix')];
        yield 'flicker' => [new ConversionFixture(type: 'gigascreen', sourceFileName: 'example.img', expectedFileName: 'gigascreen-flicker.gif', gigascreenMode: 'flicker')];
        yield 'interlace1' => [new ConversionFixture(type: 'gigascreen', sourceFileName: 'example.img', expectedFileName: 'gigascreen-interlace1.gif', gigascreenMode: 'interlace1')];
        yield 'interlace2' => [new ConversionFixture(type: 'gigascreen', sourceFileName: 'example.img', expectedFileName: 'gigascreen-interlace2.gif', gigascreenMode: 'interlace2')];
    }

    /**
     * @return iterable<string, array{ConversionFixture}>
     */
    public static function sizeFixtures(): iterable
    {
        yield 'zoom 0.25' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'zoom-025.png', zoom: 0.25)];
        yield 'zoom 0.5' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'zoom-05.png', zoom: 0.5)];
        yield 'zoom 2' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'zoom-2.png', zoom: 2.0)];
        yield 'zoom 3' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'zoom-3.png', zoom: 3.0)];
        yield 'zoom 4' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'zoom-4.png', zoom: 4.0)];
    }

    /**
     * @return iterable<string, array{ConversionFixture}>
     */
    public static function rotationFixtures(): iterable
    {
        yield '90' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'rotation-90.png', rotation: 90)];
        yield '180' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'rotation-180.png', rotation: 180)];
        yield '270' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'rotation-270.png', rotation: 270)];
    }

    /**
     * @return iterable<string, array{ConversionFixture}>
     */
    public static function filterFixtures(): iterable
    {
        yield 'prefilter atari' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'prefilter-atari.png', preFilters: ['atari'])];
        yield 'postfilter blur' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'postfilter-blur.png', zoom: 2.0, postFilters: ['blur'])];
        yield 'postfilter scanlines' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'postfilter-scanlines.png', zoom: 2.0, postFilters: ['scanlines'])];
        yield 'postfilter minSize384' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'postfilter-min-size-384.png', postFilters: ['minSize384'])];
        yield 'postfilter minSize768' => [new ConversionFixture(type: 'standard', sourceFileName: 'example.scr', expectedFileName: 'postfilter-min-size-768.png', postFilters: ['minSize768'])];
    }

    private function assertConversionMatchesExpectedFixture(ConversionFixture $fixture): void
    {
        $converter = new Converter();
        $converter
            ->setType($fixture->type)
            ->setPath(self::EXAMPLE_PATH . $fixture->sourceFileName)
            ->setZoom($fixture->zoom)
            ->setRotation($fixture->rotation);

        if ($fixture->border !== null) {
            $converter->setBorder($fixture->border);
        }
        if ($fixture->palette !== null) {
            $converter->setPalette($fixture->palette);
        }
        if ($fixture->gigascreenMode !== null) {
            $converter->setGigascreenMode($fixture->gigascreenMode);
        }
        foreach ($fixture->preFilters as $preFilter) {
            $converter->addPreFilter($preFilter);
        }
        foreach ($fixture->postFilters as $postFilter) {
            $converter->addPostFilter($postFilter);
        }

        $actualBinary = $converter->getBinary();

        self::assertIsString($actualBinary);
        self::assertNotSame('', $actualBinary);

        $expectedFilePath = self::EXPECTED_PATH . $fixture->expectedFileName;
        self::assertFileExists($expectedFilePath);

        $expectedHash = hash_file('sha256', $expectedFilePath);
        self::assertIsString($expectedHash);

        $actualHash = hash('sha256', $actualBinary);

        if ($expectedHash !== $actualHash) {
            $this->writeReceivedFixture($fixture->expectedFileName, $actualBinary);
        }

        self::assertSame($expectedHash, $actualHash);
    }

    private function writeReceivedFixture(string $expectedFileName, string $actualBinary): void
    {
        if (!is_dir(self::RECEIVED_PATH)) {
            mkdir(self::RECEIVED_PATH, recursive: true);
        }

        file_put_contents(self::RECEIVED_PATH . $expectedFileName, $actualBinary);
    }

    private function createTemporaryCacheFilePath(string $name): string
    {
        $cacheDirectory = sys_get_temp_dir() . '/zx-image-cache-test-' . bin2hex(random_bytes(8));
        mkdir($cacheDirectory);

        return $cacheDirectory . DIRECTORY_SEPARATOR . $name;
    }

    private function removeTemporaryCacheFile(string $cacheFilePath): void
    {
        if (is_file($cacheFilePath)) {
            unlink($cacheFilePath);
        }

        $cacheDirectory = dirname($cacheFilePath);
        if (is_dir($cacheDirectory)) {
            rmdir($cacheDirectory);
        }
    }
}
