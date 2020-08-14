<?php

namespace ZxImage\Plugin;

class Ssx extends Standard
{
//    use Sam;
    protected ?int $fileSize = null;
    public function convert(): ?string
    {
        if ($this->makeHandle()) {
            if ($this->fileSize === 6928) {
                $this->converter->setType('sam1');
                return $this->converter->getBinary();
            } elseif ($this->fileSize === 12304) {
                $this->converter->setType('mc');
                return $this->converter->getBinary();
            } elseif ($this->fileSize === 24580) {
                $this->converter->setType('sam3');
                return $this->converter->getBinary();
            } elseif ($this->fileSize === 24592) {
                $this->converter->setType('sam4');
                return $this->converter->getBinary();
            }
        }
        return false;
    }

}
