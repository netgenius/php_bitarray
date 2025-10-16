<?php

require __DIR__ . '/../vendor/autoload.php';

use chdemko\BitArray\BitArray as Chdemko_BitArray;


class StringBitArray implements ArrayAccess, Countable
{
  private int $size;
  private string $data;

  public function __construct(int $size)
  {
    if ($size <= 0) {
      throw new InvalidArgumentException("Size must be greater than zero");
    }

    $this->size = $size;
    $this->data = str_repeat(chr(0), ceil($size / 8));
  }

  // ArrayAccess: check if index exists
  public function offsetExists($offset): bool
  {
    return is_int($offset) && $offset >= 0 && $offset < $this->size;
  }

  // ArrayAccess: read value
  public function offsetGet($offset): bool
  {
    return (ord($this->data[$offset >> 3]) >> ($offset & 7)) & 1;
  }

  // ArrayAccess: write value
  public function offsetSet($offset, $value): void
  {
    // Note tried unpack() and pack() here but that was much slower.
    $mask = (1 << ($offset & 7));
    $bits = ord($this->data[$offset >> 3]);
    $this->data[$offset >> 3] = chr($value ? ($bits | $mask) : ($bits & ~$mask));
  }

  // ArrayAccess: unset value
  public function offsetUnset($offset): void
  {
    // This is all we can do to "unset" a bit - set it to 0.
    $this->offsetSet($offset, 0);
  }

  // Countable
  public function count(): int
  {
    return $this->size;
  }
}

class Sieve
{
  private $sieve, $limit;

  public function __construct(int $limit, int $storage)
  {
    // Storage needed is half the limit (odd numbers only).
    $size = ($limit + 1) >> 1;

    if ($storage == 1) {
      // Standard PHP array (fast, but very memory hungry).
      $sieve = array_fill(0, $size, 0);
    } elseif ($storage == 2) {
      // PHP SplFixedArray (fast, slightly less memory hungry).
      $sieve = new SplFixedArray($size);
    } elseif ($storage == 3) {
      // PHP string as an array (fast, high memory usage).
      $sieve = str_repeat('0', $size);
    } elseif ($storage == 4) {
      // Local StringBitArray class (slow, minimal memory usage).
      $sieve = new StringBitArray($size);
    } elseif ($storage == 5) {
      // Chdemko_BitArray (very slow, high memory usage).
      $sieve = Chdemko_BitArray::fromString(str_repeat('0', $size));
    } else {
      // BitArray extension (very fast, minimal memory usage)
      $sieve = new BitArray($size);
    }

    // Optimised Sieve of Eratosthenes.
    $limit_sqrt = floor(sqrt($limit));
    for ($n = 3; $n < $limit_sqrt; $n += 2) {
      if ($sieve[$n >> 1] == false) {
        // Flag multiples of $n as non-primes.
        $idx = ($n * $n) >> 1;
        $idx_last = ($limit - 1) >> 1;
        for (; $idx <= $idx_last; $idx += $n) {
          $sieve[$idx] = true;
        }
      }
    }

    $this->limit = $limit;
    $this->sieve = $sieve;
  }

  public function report(int $print = 0)
  {
    $sieve = $this->sieve;
    $limit = $this->limit;

    // Include the prime 2 in results.
    $count = 1;
    $last = $check = 2;

    for ($n = 3; $n < $limit; $n += 2) {
      if (($sieve[$n >> 1]) == 0) {
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
  4 => 'string bitarray',
  5 => 'Chdemko BitArray',
  6 => 'BitArray extension',
];
$storage = $argv[1] ?? 0;
if ($storage < 1 || $storage > 6) {
  echo "Usage: php $argv[0] 1|2|3|4|5|6\n";
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

$t = microtime(true);
$sieve = new Sieve($limit, $storage);
$t = microtime(true) - $t;

$count = $sieve->report(0);
printf(
  "Found %s primes at %s million/second\n",
  number_format($count),
  number_format($count / 1000000 / $t, 1),
);

$mem = memory_get_peak_usage(true);
printf("Peak memory used: %s MB\n", number_format($mem >> 20));
exit(0);

// ============================================================================= 

// End of file