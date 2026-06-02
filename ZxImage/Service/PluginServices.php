<?php

declare(strict_types=1);

namespace ZxImage\Service;

final readonly class PluginServices
{
    public function __construct(
        public FileLoader $fileLoader = new FileLoader(),
        public PaletteService $paletteService = new PaletteService(),
        public ImageProcessor $imageProcessor = new ImageProcessor(),
        public ImageEncoder $imageEncoder = new ImageEncoder(),
    ) {
    }
}
