<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

use ZxImage\Dto\ParsedScreen;

readonly class MghParsedScreens
{
    public function __construct(
        public ParsedScreen $first,
        public ParsedScreen $second,
    ) {
    }
}
