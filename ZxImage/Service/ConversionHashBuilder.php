<?php

declare(strict_types=1);

namespace ZxImage\Service;

use ZxImage\Dto\ConversionHashInput;
use ZxImage\Enum\PluginType;

final readonly class ConversionHashBuilder
{
    public function build(ConversionHashInput $input): ?string
    {
        if ($input->sourceFileContents === '' && $input->sourceFilePath === '') {
            return null;
        }

        $hashInput = $this->buildSourceHashInput($input);
        $hashInput .= $input->type;

        $pluginType = PluginType::tryFrom($input->type);
        if ($pluginType?->usesGigascreenModeInHash() === true) {
            $hashInput .= $input->gigascreenMode;
        }

        $hashInput .= $input->border;
        $hashInput .= $input->palette;
        $hashInput .= $input->zoom;
        $hashInput .= implode($input->preFilters);
        $hashInput .= implode($input->postFilters);

        if ($input->rotation > 0) {
            $hashInput .= $input->rotation;
        }

        return md5($hashInput);
    }

    private function buildSourceHashInput(ConversionHashInput $input): string
    {
        if (is_file($input->sourceFilePath)) {
            return $input->sourceFilePath . filemtime($input->sourceFilePath);
        }

        if ($input->sourceFileContents !== '') {
            return md5($input->sourceFileContents);
        }

        return '';
    }
}
