<?php

/*
 * PHP BitArray implementation.
 *
 * Copyright (c) 2025 Andy Inman <https://github.com/andy-netgenius>
 * License: MIT
 */

declare(strict_types=1);

namespace PhpBitArray;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use SplFixedArray;

abstract class PhpBitArray implements ArrayAccess, Countable
{
  protected const BITS_PER_INT = 8 * PHP_INT_SIZE;
  protected const DIVIDE_BY_64 = (PHP_INT_SIZE == 4) ? 5 : 6;
  protected const MASK_64_BITS = self::BITS_PER_INT - 1;

  protected int $size;
  protected mixed $data;

  public function __construct(int $size)
  {
    if ($size <= 0) {
      throw new InvalidArgumentException("Size must be greater than zero");
    }
    $this->size = $size;
  }

  // ArrayAccess: check if index exists
  public function offsetExists(mixed $offset): bool
  {
    return (is_int($offset) && $offset >= 0 && $offset < $this->size);
  }

  // ArrayAccess: unset value
  public function offsetUnset(mixed $offset): void
  {
    // This is all we can do to "unset" a bit - set it to 0.
    $this->offsetSet($offset, 0);
  }

  // ArrayAccess: read value
  public function offsetGet(mixed $offset): int
  {
    return (($this->data[$offset >> self::DIVIDE_BY_64]) >> ($offset & self::MASK_64_BITS)) & 1;
  }

  // ArrayAccess: write value
  public function offsetSet(mixed $offset, mixed $value): void
  {
    if ($value) {
      $this->data[$offset >> self::DIVIDE_BY_64] |= (1 << ($offset & self::MASK_64_BITS));
    } else {
      $this->data[$offset >> self::DIVIDE_BY_64] &= ~(1 << ($offset & self::MASK_64_BITS));
    }
  }

  // Countable
  public function count(): int
  {
    return $this->size;
  }

  // Helper to calculate the number of elements needed (rounding up).
  protected function sizeCalc(int $size, int $bitsPerElement = self::BITS_PER_INT): int
  {
    return intdiv($size + $bitsPerElement - 1, $bitsPerElement);
  }
}

class ArrayBitArray extends PhpBitArray
{
  public function __construct(int $size)
  {
    parent::__construct($size);
    $this->data = array_fill(0, self::sizeCalc($size), 0);
  }
}

class SplBitArray extends PhpBitArray
{
  public function __construct(int $size)
  {
    parent::__construct($size);
    $this->data = new SplFixedArray(self::sizeCalc($size));
  }
}

class StringBitArray extends PhpBitArray
{
  public function __construct(int $size)
  {
    parent::__construct($size);
    $this->data = str_repeat(chr(0), self::sizeCalc($size, 8));
  }

  // ArrayAccess: read value
  public function offsetGet($offset): int
  {
    return (ord($this->data[$offset >> 3]) >> ($offset & 7)) & 1;
  }

  // ArrayAccess: write value
  public function offsetSet($offset, $value): void
  {
    if ($value) {
      $this->data[$offset >> 3] = chr(ord($this->data[$offset >> 3]) | (1 << ($offset & 7)));
    } else {
      $this->data[$offset >> 3] = chr(ord($this->data[$offset >> 3]) & ~(1 << ($offset & 7)));
    }
  }
}
