<?php

class Sieve {

  private $sieve, $limit;

  public function __construct(int $limit, int $type = 3) {

    if ($type == 1) {
      // Standard PHP array.
      $sieve = array_fill(0, ($limit >> 1), 0);
    } elseif ($type == 2) {
      // String as array.
      $sieve = str_repeat('0', ($limit >> 1));
    } else {
      // BitArray extension.
      $sieve = new BitArray($limit >> 1);
    }

    // Optimised Sieve of Eratosthenes.
    $limit_sqrt = floor(sqrt($limit));
    for ($n = 3; $n < $limit_sqrt; $n += 2) {
      if ($sieve[($n) >> 1] == false) {
        // Flag multiples of $n as non-primes.
        $idx = (($n * $n)) >> 1;
        $idx_last = ($limit - 1) >> 1;
        for (; $idx <= $idx_last; $idx += $n) {
          $sieve[$idx] = true;
        }
      }
    }

    $this->limit = $limit;
    $this->sieve = $sieve;
  }

  public function report(int $print = 0) {
    $sieve = $this->sieve;
    $limit = $this->limit;
    $check = $count = $last = 0;
    for ($n = 3; $n < $limit; $n += 2) {
      if (($sieve[($n) >> 1] ?? 0) == 0) {
        $prime = $n;
        $count++;
        $check ^= $prime;
        $last = $prime;
        if ($prime < $print) {
          echo "$prime ";
        }
      }
    }

    if ($limit == 100000000) {
      if ($check != 17422160 || $count != 5761454 || $last != 99999989) {
        echo "check: $check count: $count last: $last\n";
        throw new Exception("Sieve error");
      }
    } 
    return $count;
  }
}

// Process command line parameters.
$type = $argv[1] ?? 0;
if ($type < 1 || $type > 3) {
  echo "Usage: php $argv[0] 1|2|3\n";
  echo " 1 = Standard array\n";
  echo " 2 = String as array\n";
  echo " 3 = BitArray extension\n";
  exit(1);
}

$limit=100000000;
printf("Finding all primes up to %s using storage type %d ...\n",
  number_format($limit),
  $type,
);

$t = microtime(true);
$sieve = new Sieve($limit, $type);
$t = microtime(true) - $t;

$count = $sieve->report(0);
printf("Found %s primes at %s million/second\n",
  number_format($count),
  number_format($count / 1000000 / $t, 1),
);

$mem = memory_get_peak_usage(true) >> 20;
printf("Peak memory used: %s MB\n", number_format($mem));
