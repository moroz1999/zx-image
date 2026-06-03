<?php

declare(strict_types=1);

namespace ZxImage\Enum;

enum GigascreenMode: string
{
    case Mix = 'mix';
    case Flicker = 'flicker';
    case Interlace1 = 'interlace1';
    case Interlace2 = 'interlace2';

    public function usesFlickerFrames(): bool
    {
        return match ($this) {
            self::Flicker, self::Interlace1, self::Interlace2 => true,
            self::Mix => false,
        };
    }

    public function interlaceLineHeight(): ?int
    {
        return match ($this) {
            self::Interlace1 => 1,
            self::Interlace2 => 2,
            default => null,
        };
    }
}
