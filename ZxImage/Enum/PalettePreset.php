<?php

declare(strict_types=1);

namespace ZxImage\Enum;

enum PalettePreset: string
{
    case Pulsar = 'pulsar';
    case Orthodox = 'orthodox';
    case Alone = 'alone';
    case Electroscale = 'electroscale';
    case Srgb = 'srgb';

    public function paletteString(): string
    {
        return match ($this) {
            self::Pulsar => '00,76,CD,E9,FF,9F:FF,00,00;00,FF,00;00,00,FF',
            self::Orthodox => '00,76,CD,E9,FF,9F:D0,00,00;00,E4,00;00,00,FF',
            self::Alone => '00,60,A0,E0,FF,A0:FF,00,00;00,FF,00;00,00,FF',
            self::Electroscale => '4F,A1,DD,F0,FF,BD:39,73,1D;3C,77,1E;46,8C,23',
            self::Srgb => '00,96,CD,E8,FF,BC:FF,00,00;00,FF,00;00,00,FF',
        };
    }
}
