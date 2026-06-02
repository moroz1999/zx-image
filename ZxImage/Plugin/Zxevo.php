<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

use GdImage;
use ZxImage\Converter;
use ZxImage\Dto\Frame;
use ZxImage\Dto\FrameSet;
use ZxImage\Dto\PluginGeometry;
use ZxImage\Dto\PluginInput;
use ZxImage\Dto\RenderSettings;
use ZxImage\Service\PluginServices;

class Zxevo implements FramePluginInterface
{
    private PluginInput $input;
    private PluginGeometry $geometry;
    private RenderSettings $renderSettings;
    private PluginServices $services;

    public function __construct(
        ?string $sourceFilePath = null,
        ?string $sourceFileContents = null,
        ?Converter $converter = null,
    ) {
        $this->input = new PluginInput($sourceFilePath, $sourceFileContents);
        $this->geometry = new PluginGeometry(width: 320, height: 200, usesBorder: false);
        $this->renderSettings = new RenderSettings();
        $this->services = new PluginServices();
    }

    public function configure(RenderSettings $settings): void
    {
        $this->renderSettings = $settings;
    }

    public function convertFrames(): ?FrameSet
    {
        if ($this->input->sourceFilePath === null || !file_exists($this->input->sourceFilePath)) {
            return null;
        }

        $sizes = getimagesize($this->input->sourceFilePath);
        if ($sizes !== false) {
            $this->geometry = $this->geometry->withDimensions($sizes[0], $sizes[1]);
        }

        $gdObject = imagecreatefrombmp($this->input->sourceFilePath);
        if ($gdObject === false) {
            return null;
        }

        $colorsAmount = imagecolorstotal($gdObject);
        if ($colorsAmount > 16 || $colorsAmount === 0) {
            return null;
        }

        $image = $this->adjustImage($gdObject);
        $colorTable = $this->services->paletteService->buildColorTable($this->renderSettings->paletteString);

        return new FrameSet(
            [new Frame($image)],
            $this->renderSettings,
            $this->geometry->toRenderGeometry(),
            $colorTable,
        );
    }

    private function adjustImage(GdImage $image): GdImage
    {
        $colorsAmount = imagecolorstotal($image);
        for ($i = 0; $i < $colorsAmount; $i++) {
            $color = imagecolorsforindex($image, $i);
            $color['red'] = (int)round($color['red'] / 85) * 85;
            $color['green'] = (int)round($color['green'] / 85) * 85;
            $color['blue'] = (int)round($color['blue'] / 85) * 85;
            imagecolorset($image, $i, $color['red'], $color['green'], $color['blue']);
        }

        return $image;
    }
}
