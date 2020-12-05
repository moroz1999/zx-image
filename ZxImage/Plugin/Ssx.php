<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

class Ssx extends Standard
{
    /**
     * @var int|null
     */
    protected $strictFileSize;

    /**
     * @return string|null
     */
    public function convert()
    {
        if ($this->makeHandle()) {
            $binary = null;
            if ($this->strictFileSize === 6928) {
                $this->converter->setType('standard');
            } elseif ($this->strictFileSize === 12304) {
                $this->converter->setType('mc');
            } elseif ($this->strictFileSize === 24580) {
                $this->converter->setType('sam3');
            } elseif ($this->strictFileSize === 24592) {
                $this->converter->setType('sam4');
            }elseif ($this->strictFileSize === 98304) {
                $this->converter->setType('ssxRaw');
            }
            if ($binary = $this->converter->getBinary()) {
                $this->resultMime = $this->converter->getResultMime();
            }
            return $binary;
        }
        return null;
    }

}
