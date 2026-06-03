<?php

declare(strict_types=1);

namespace ZxImage\Plugin\Multiartist;

readonly class MghData
{
    public function __construct(
        public int $mode,
        public MghBorders $borders,
        public MghDimensions $dimensions,
        /** @var int[] */
        public array $firstPixelsBytes,
        /** @var int[] */
        public array $secondPixelsBytes,
        /** @var int[] */
        public array $firstAttributesBytes,
        /** @var int[] */
        public array $secondAttributesBytes,
        /** @var int[] */
        public array $firstOuterAttributesBytes,
        /** @var int[] */
        public array $secondOuterAttributesBytes,
    ) {
    }
}
