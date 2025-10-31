<?php

declare(strict_types=1);

require __DIR__ . '/PhpBitArray.php';

use PhpBitArray\ArrayBitArray;
use PhpBitArray\SplBitArray;
use PhpBitArray\StringBitArray;

class BitArrayTest
{
  private mixed $bitStorage;
  private int $size;

  public function __construct(int $size, int $storage_type)
  {
    switch ($storage_type) {
      case 1:
        $bitStorage = array_fill(0, $size, 0);
        break;
      case 2:
        $bitStorage = new SplFixedArray($size);
        break;
      case 3:
        $bitStorage = str_repeat('0', $size);
        break;
      case 4:
        $bitStorage = new ArrayBitArray($size);
        break;
      case 5:
        $bitStorage = new SplBitArray($size);
        break;
      case 6:
        $bitStorage = new StringBitArray($size);
        break;
      case 7:
        $bitStorage = new BitArray($size);
        break;
      default:
        throw new Exception("Invalid storage type [$storage].");
    }

    $this->bitStorage = $bitStorage;
    $this->size = $size;
  }

  public function dryRun(): void
  {
    $limit = $this->size - 1;
    srand(42);

    for ($i = 0; $i <= $limit; $i++) {
      rand(0, $limit); rand(0, $limit) ^ rand(0, 1);
      rand(0, $limit); rand(0, $limit) ^ rand(0, 1);
      rand(0, $limit); rand(0, $limit) ^ rand(0, 1);
      rand(0, $limit); rand(0, $limit) ^ rand(0, 1);
    }
  }

  public function randomReadWriteTest(): void
  {
    $limit = $this->size - 1;
    srand(42);

    for ($i = 0; $i <= $limit; $i++) {
      $this->bitStorage[rand(0, $limit)] = $this->bitStorage[rand(0, $limit)] ^ rand(0, 1);
      $this->bitStorage[rand(0, $limit)] = $this->bitStorage[rand(0, $limit)] ^ rand(0, 1);
      $this->bitStorage[rand(0, $limit)] = $this->bitStorage[rand(0, $limit)] ^ rand(0, 1);
      $this->bitStorage[rand(0, $limit)] = $this->bitStorage[rand(0, $limit)] ^ rand(0, 1);
    }
  }

  // Check results and show summary.
  public function report(int $print = 0)
  {
    $size = $this->size;
    $context = hash_init('md5');

    for ($i = 0; $i < $size; $i++) {
      $bit = (string) (int) $this->bitStorage[$i];
      hash_update($context, $bit);
    }

    $md5hash = hash_final($context);
    $expected = [
      100 => '3dec65f58cf06dd024c31364c740bef3',
      1000 => '39b3bde7b43addd74b48246dabf1d1eb',
      10000 => 'c1dc88aee8df08b473d979c257a0dfe5',
      100000 => '6bc7f68e101d7c60fde79eefec99983e',
      1000000 => '21caf8ccd5317bf4dafb902e94554dc3',
      10000000 => '26ecfcf4303092262812e0d777d3fcde',
      100000000 => '8c85c505c804f6881275ac3448d567be',
    ];

    if (isset($expected[$size])) {
      if ($md5hash != $expected[$size]) {
        printf("Fail - md5: %s\n", $md5hash);
        throw new Exception("Failed verification check.");
      }
    } else {
      echo "Verification check for size $size is not defined - md5: $md5hash\n";
      echo "Checks are defined for sizes: " . implode(', ', array_keys($expected)) . "\n";
    }

    return $size;
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
  7 => 'BitArray extension',
];

$storage = $argv[1] ?? -1;
if (!isset($storages[$storage])) {
  printf("Usage: php %s %s [size] [repeats]\n", $argv[0], implode('|', array_keys($storages)));
  foreach ($storages as $k => $v) {
    echo "  $k: $v\n";
  }
  exit(1);
}

// Read command line parameters, convert to int.
$storage = intval($argv[1] ?? 0);
$size = intval($argv[2] ?? 1000000);
$repeats = intval($argv[3] ?? 3);

printf(
  "Storage for %s bits, method: '%s' storage.\n",
  number_format($size),
  $storages[$storage],
);

// Test single storage type or all types.
$first = ($storage == 0) ? 1 : $storage;
$last = ($storage == 0) ? array_key_last($storages) : $storage;

for ($i = $first; $i <= $last; $i++) {

  if ($first != $last) {
    printf("%s: \n", $storages[$i]);
  }

  for ($j = 1; $j <= $repeats; $j++) {
    memory_reset_peak_usage();
    $mem = memory_get_peak_usage(false);
    $bitStorageTest = new BitArrayTest($size, $i);
    $mem = memory_get_peak_usage(false) - $mem;

    // Measure test overhead time.
    $t = hrtime(true);
    $bitStorageTest->dryRun();
    $t_overhead = hrtime(true) - $t;

    // Measure array random access time.
    $t = hrtime(true);
    $bitStorageTest->randomReadWriteTest();
    $t_readwrite = hrtime(true) - $t - $t_overhead;

    $bitStorageTest->report();
    unset($bitStorageTest);

    // Calculate memory that would be needed by ideal storage.
    $mem_needed = $size / 8;
    printf(
      "  [$j] Storage memory $mem: %s (%s%% overhead). Speed: %s million/second.\n",
      ($mem < (1024 * 1024)) ? number_format($mem / 1024, 2) . " KB" : number_format($mem / 1024 / 1024, 2) . " MB",
      number_format((100 * $mem / $mem_needed) - 100),
      number_format(1000 * $size / $t_readwrite, 2),
      number_format($size),
    );

    gc_collect_cycles();
  }
}
exit(0);

// ============================================================================= 
// End of file.
