<?php

declare(strict_types=1);

namespace ZxImage\Service;

final readonly class ConversionCache
{
    public function getMime(string $filePath): ?string
    {
        if (is_file($filePath) === false) {
            return null;
        }

        $imageInfo = getimagesize($filePath);
        if ($imageInfo === false) {
            return null;
        }

        return $imageInfo['mime'];
    }

    /**
     * @param callable(): ?string $binaryGenerator
     */
    public function loadOrGenerate(string $filePath, callable $binaryGenerator): ?string
    {
        if (file_exists($filePath)) {
            $binary = file_get_contents($filePath);
            return $binary === false ? null : $binary;
        }

        $binary = $binaryGenerator();
        if ($binary !== null) {
            file_put_contents($filePath, $binary);
        }

        return $binary;
    }
}
