<?php

declare(strict_types=1);

namespace ZxImage\Enum;

use ZxImage\Filter\Atari;
use ZxImage\Filter\Blur;
use ZxImage\Filter\Filter;
use ZxImage\Filter\MinSize384;
use ZxImage\Filter\MinSize768;
use ZxImage\Filter\Scanlines;

enum FilterType: string
{
    case Atari = 'atari';
    case Blur = 'blur';
    case MinSize384 = 'minSize384';
    case MinSize768 = 'minSize768';
    case Scanlines = 'scanlines';

    public function createFilter(): Filter
    {
        return match ($this) {
            self::Atari => new Atari(),
            self::Blur => new Blur(),
            self::MinSize384 => new MinSize384(),
            self::MinSize768 => new MinSize768(),
            self::Scanlines => new Scanlines(),
        };
    }
}
