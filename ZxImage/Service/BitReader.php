<?php

declare(strict_types=1);

namespace ZxImage\Service;

use RuntimeException;

final class BitReader
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
        if ($read === false || $read === '') {
            return null;
        }
        return ord($read);
    }

    public function readWord(): ?int
    {
        $leastSignificantByte = $this->readByte();
        if ($leastSignificantByte === null) {
            return null;
        }
        $mostSignificantByte = $this->readByte();
        if ($mostSignificantByte === null) {
            return null;
        }
        return $mostSignificantByte * 256 + $leastSignificantByte;
    }

    /**
     * @return list<int>
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
     * @return list<int>
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
        if ($result === false || strlen($result) !== $length) {
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
        if ($current === false) {
            throw new RuntimeException('Unable to get current stream position');
        }
        fseek($this->handle, 0, SEEK_END);
        $size = ftell($this->handle);
        if ($size === false) {
            throw new RuntimeException('Unable to get stream size');
        }
        fseek($this->handle, $current);
        return $size;
    }

}
