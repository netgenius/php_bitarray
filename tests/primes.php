<?php
declare(strict_types=1);

require __DIR__ . '/PhpBitArray.php';
use PhpBitArray\ArrayBitArray;
use PhpBitArray\SplBitArray;
use PhpBitArray\StringBitArray;

require __DIR__ . '/../vendor/autoload.php';
use chdemko\BitArray\BitArray as Chdemko_BitArray;

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

  // Build our (optimised) Sieve of Eratosthenes.
  public function build()
  {
    $limit_sqrt = floor(sqrt($this->limit));
    $idx_last = ($this->limit - 1) >> 1;

    for ($n = 3; $n < $limit_sqrt; $n += 2) {
      if ($this->sieve[$n >> 1] == false) {
        // Flag multiples of $n as non-primes.
        for ($idx = ($n * $n) >> 1; $idx <= $idx_last; $idx += $n) {
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
    $context = hash_init('md5');

    for ($n = 3; $n < $limit; $n += 2) {
      if (($this->sieve[$n >> 1]) == 0) {
        $prime = $n;
        $count++;
        $check = (($check << 1) & 0x7fffffffffffffff) ^ $prime;
        hash_update($context, "$prime");
        $last = $prime;
        if ($prime < $print) {
          echo "$prime ";
        }
      }
    }

    $md5hash = hash_final($context);
    $expected = [
      100 => 'e8947ccab104806a55c163fe6b70868b',
      1000 => 'a34cb0b83d275505152b6a4a64dcf8e2',
      10000 => '50c97f40b0d59694c25a2e965119efe8',
      100000 => '7ed99b3a238c1cec2319c44842aaac30',
      1000000 => '56bf6278c0864d578a03f12fa552d607',
      10000000 => 'a06eadab8fb4c8d7753a06029b1c2b23',
      100000000 => '7ed04acc90324fb31b69b44aa8ea5290',
      1000000000 => '6cad4c354d3fd3a39f002f9d51dd2f5c',
    ];

    if (isset($expected[$limit])) {
      if ($md5hash != $expected[$limit]) {
        printf("md5: %s count: %d last: %d\n", $md5hash, $count, $last);
        throw new Exception("Failed verification check");
      }
    } else {
      echo "Verification check for limit $limit is not defined - md5: $md5hash\n";
      echo "Checks are defined for limits: " . implode(', ', array_keys($expected)) . "\n";
    }

    return $count;
  }
}

// ============================================================================= 
// Main program
// =============================================================================

// Process command line parameters.
$storages = [
  0 => 'ALL',
  1 => 'Array direct',
  2 => 'SplFixedArray direct',
  3 => 'String direct',
  4 => 'Array packed',
  5 => 'SplFixedArray packed',
  6 => 'String packed',
  7 => 'Chdemko BitArray',
  8 => 'BitArray extension',
];

$storage = $argv[1] ?? -1;
if (!isset($storages[$storage])) {
  printf("Usage: php %s %s [limit] [repeats]\n", $argv[0], implode('|', array_keys($storages)));
  foreach ($storages as $k => $v) {
    echo "  $k: $v\n";
  }
  exit(1);
}

$storage = $argv[1] ?? 0;
$limit = $argv[2] ?? 10000000;
$loops = $argv[3] ?? 3;

printf(
  "Finding primes below %s - storage for %s bits is needed. Method: '%s' storage.\n",
  number_format($limit),
  number_format($limit / 2),
  $storages[$storage],
);

// Test single storage type or all types.
$first = ($storage == 0) ? 1 : $storage;
$last = ($storage == 0) ? array_key_last($storages) : $storage;

for ($i = $first; $i <= $last; $i++) {

  if ($first != $last) {
    printf("%s: \n", $storages[$i]);
  }

  for ($j = 1; $j <= $loops; $j++) {
    memory_reset_peak_usage();
    $sieve = new Sieve($limit, $i);

    $t = microtime(true);
    $sieve->build();
    $t = microtime(true) - $t;

    $mem = memory_get_peak_usage(true);
    $count = $sieve->report(0);
    unset($sieve);

    printf(
      "  [$j] Peak memory: %s MB. Speed: %s million/second. Found: %s primes.\n",
      number_format($mem >> 20),
      number_format($count / 1000000 / $t, 2),
      number_format($count),
    );

    gc_collect_cycles();
  }
}
exit(0);

// ============================================================================= 
// End of file
