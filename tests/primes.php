<?php

require __DIR__ . '/../vendor/autoload.php';

use chdemko\BitArray\BitArray as Chdemko_BitArray;


class BitArray2 implements ArrayAccess, Countable
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
  public function offsetExists($offset): bool
  {
    return (is_int($offset) && $offset >= 0 && $offset < $this->size);
  }

  // ArrayAccess: unset value
  public function offsetUnset($offset): void
  {
    // This is all we can do to "unset" a bit - set it to 0.
    $this->offsetSet($offset, 0);
  }

  // ArrayAccess: read value
  public function offsetGet($offset): bool
  {
    return (($this->data[$offset >> self::DIVIDE_BY_64]) >> ($offset & self::MASK_64_BITS)) & 1;
  }

  // ArrayAccess: write value
  public function offsetSet($offset, $value): void
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
}

class ArrayBitArray extends BitArray2
{
  public function __construct(int $size)
  {
    parent::__construct($size);
    $this->data = array_fill(0, ceil($size / self::BITS_PER_INT), 0);
  }
}

class SplBitArray extends BitArray2
{
  public function __construct(int $size)
  {
    parent::__construct($size);
    $this->data = new SplFixedArray(ceil($size / self::BITS_PER_INT));
  }
}

class StringBitArray extends BitArray2
{
  public function __construct(int $size)
  {
    parent::__construct($size);
    $this->data = str_repeat(chr(0), ceil($size / 8));
  }

  // ArrayAccess: read value
  public function offsetGet($offset): bool
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

class Sieve
{
  private mixed $sieve;
  private int $limit;

  public function __construct(int $limit, int $storage)
  {
    // Storage needed is half the limit (odd numbers only).
    $size = ($limit + 1) >> 1;

    switch ($storage) {
      case 1:
        $sieve = array_fill(0, $size, 0);
        break;
      case 2:
        $sieve = new SplFixedArray($size);
        break;
      case 3:
        $sieve = str_repeat('0', $size);
        break;
      case 4:
        $sieve = new ArrayBitArray($size);
        break;
      case 5:
        $sieve = new SplBitArray($size);
        break;
      case 6:
        $sieve = new StringBitArray($size);
        break;
      case 7:
        $sieve = Chdemko_BitArray::fromString(str_repeat('0', $size));
        break;
      default:
        $sieve = new BitArray($size);
        break;
    }

    $this->limit = $limit;
    $this->sieve = $sieve;
  }

  // Optimised Sieve of Eratosthenes.
  public function build() {
    $limit_sqrt = floor(sqrt($this->limit));
    for ($n = 3; $n < $limit_sqrt; $n += 2) {
      if ($this->sieve[$n >> 1] == false) {
        // Flag multiples of $n as non-primes.
        $idx = ($n * $n) >> 1;
        $idx_last = ($this->limit - 1) >> 1;
        for (; $idx <= $idx_last; $idx += $n) {
          $this->sieve[$idx] = true;
        }
      }
    }
  }

  // Check results and show summary.
  public function report(int $print = 0)
  {
    $limit = $this->limit;

    // Include the prime 2 in results.
    $count = 1;
    $last = $check = 2;

    for ($n = 3; $n < $limit; $n += 2) {
      if (($this->sieve[$n >> 1]) == 0) {
        $prime = $n;
        $count++;
        $check = (($check << 1) & 0x7fffffffffffffff) ^ $prime;
        $last = $prime;
        if ($prime < $print) {
          echo "$prime ";
        }
      }
    }

    if ($limit == 100000000) {
      if ($check != 0x12d9d692ec972a57 || $count != 5761455 || $last != 99999989) {
        printf("check: %x count: %d last: %d\n", $check, $count, $last);
        throw new Exception("Failed verification checks");
      }
    } elseif ($limit == 500000000) {
      if ($check != 0x3f6acc823798e123 || $count != 26355867 || $last != 499999993) {
        printf("check: %x count: %d last: %d\n", $check, $count, $last);
        throw new Exception("Failed verification checks");
      }
    }
    else {
      echo "No verification for limit $limit\n";
    }

    return $count;
  }
}

// ============================================================================= 
// Main program
// =============================================================================

// Process command line parameters.
$storages = [
  1 => 'array',
  2 => 'SplFixedArray',
  3 => 'string',
  4 => 'array bitarray',
  5 => 'SplFixedArray bitarray',
  6 => 'string bitarray',
  7 => 'Chdemko BitArray',
  8 => 'BitArray extension',
];

$storage = $argv[1] ?? 0;
if ($storage < 1 || $storage > count($storages)) {
  printf("Usage: php %s %s\n", $argv[0], implode('|', array_keys($storages)));
  foreach ($storages as $k => $v) {
    echo "  $k: $v\n";
  }
  exit(1);
}

//$limit=500000000;
$limit = 100000000;
printf(
  "Finding all primes below %s using '%s' storage...\n",
  number_format($limit),
  $storages[$storage]
);

$sieve = new Sieve($limit, $storage);
$t = microtime(true);
$sieve->build();
$t = microtime(true) - $t;

$count = $sieve->report(0);
printf(
  "Found %s primes at %s million/second\n",
  number_format($count),
  number_format($count / 1000000 / $t, 2),
);

$mem = memory_get_peak_usage(true);
printf("Peak memory used: %s MB\n", number_format($mem >> 20));
exit(0);

// ============================================================================= 

// End of file
