<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

final readonly class MghData
{
    public function __construct(
        public int $mode,
        public MghBorders $borders,
        public MghDimensions $dimensions,
        /** @var list<int> */
        public array $firstPixelsBytes,
        /** @var list<int> */
        public array $secondPixelsBytes,
        /** @var list<int> */
        public array $firstAttributesBytes,
        /** @var list<int> */
        public array $secondAttributesBytes,
        /** @var list<int> */
        public array $firstOuterAttributesBytes,
        /** @var list<int> */
        public array $secondOuterAttributesBytes,
    ) {
    }
}
