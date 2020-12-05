<?php

declare(strict_types=1);

namespace ZxImage\Plugin;

class Ssx extends Standard
{
    protected ?int $strictFileSize;

    public function convert(): ?string
    {
        if ($this->makeHandle()) {
            if ($this->strictFileSize === 6928) {
                $this->converter->setType('standard');
                return $this->converter->getBinary();
            } elseif ($this->strictFileSize === 12304) {
                $this->converter->setType('mc');
                return $this->converter->getBinary();
            } elseif ($this->strictFileSize === 24580) {
                $this->converter->setType('sam3');
                return $this->converter->getBinary();
            } elseif ($this->strictFileSize === 24592) {
                $this->converter->setType('sam4');
                return $this->converter->getBinary();
            }
        }
        return false;
    }

}
