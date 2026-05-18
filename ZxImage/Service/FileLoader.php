<?php

declare(strict_types=1);

namespace ZxImage\Service;

readonly class FileLoader
{
    public function openSource(?string $path, ?string $contents, ?int $requiredSize): ?BitReader
    {
        if ($path !== null && is_file($path)) {
            $actualSize = filesize($path);
            if ($requiredSize !== null && $requiredSize !== $actualSize) {
                return null;
            }
            $handle = fopen($path, 'rb');
            return new BitReader($handle);
        }

        if ($contents !== null && $contents !== '') {
            $actualSize = strlen($contents);
            if ($requiredSize !== null && $requiredSize !== $actualSize) {
                return null;
            }
            $handle = fopen('php://memory', 'wb+');
            fwrite($handle, $contents);
            rewind($handle);
            return new BitReader($handle);
        }

        return null;
    }
}
