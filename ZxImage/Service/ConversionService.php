<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\ConversionRequest;
use ZxImage\Dto\RenderedImage;

final readonly class ConversionService
{
    public function __construct(
        private PluginFactory $pluginFactory = new PluginFactory(),
        private OutputRenderer $outputRenderer = new OutputRenderer(),
    ) {
    }

    public function convert(ConversionRequest $request): ?RenderedImage
    {
        $plugin = $this->pluginFactory->create(
            $request->type,
            $request->input->sourceFilePath,
            $request->input->sourceFileContents,
        );
        if ($plugin === null) {
            return null;
        }

        $plugin->configure($request->renderSettings);
        $frameSet = $plugin->convertFrames();
        if ($frameSet === null) {
            return null;
        }

        return $this->outputRenderer->render($frameSet);
    }
}
