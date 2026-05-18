<?php

declare(strict_types=1);

namespace ZxImage\Service;

class BitReader
{
    /** @var resource */
    private $handle;

    /**
     * @param resource $handle
     */
    public function __construct($handle)
    {
        $this->handle = $handle;
    }

    public function readByte(): ?int
    {
        $read = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }
        return ord($read);
    }

    public function readWord(): ?int
    {
        $b1 = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }
        $b2 = fread($this->handle, 1);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }
        return ord($b2) * 256 + ord($b1);
    }

    /**
     * @return int[]
     */
    public function readBytes(int $count): array
    {
        $result = [];
        while ($count--) {
            $byte = $this->readByte();
            if ($byte === null) {
                break;
            }
            $result[] = $byte;
        }
        return $result;
    }

    /**
     * @return int[]
     */
    public function readWords(int $count): array
    {
        $result = [];
        while ($count--) {
            $word = $this->readWord();
            if ($word === null) {
                break;
            }
            $result[] = $word;
        }
        return $result;
    }

    public function readString(int $length): ?string
    {
        $result = fread($this->handle, $length);
        if (feof($this->handle)) {
            fclose($this->handle);
            return null;
        }
        return $result;
    }

    public function seek(int $offset): void
    {
        fseek($this->handle, $offset);
    }

    public function getSize(): int
    {
        $current = ftell($this->handle);
        fseek($this->handle, 0, SEEK_END);
        $size = ftell($this->handle);
        fseek($this->handle, $current);
        return $size;
    }

    public static function bit(int $byte, int $position): int
    {
        return ($byte >> $position) & 1;
    }

    public static function bits(int $byte, int $offset, int $length): int
    {
        $mask = (1 << $length) - 1;
        return ($byte >> $offset) & $mask;
    }
}
