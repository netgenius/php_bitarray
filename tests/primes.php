<?php

class BitSieve {

  public function __construct(int $limit) { 
    $sieve = new BitArray(($limit - 2) >> 1);

    $limit_sqrt = floor(sqrt($limit));
    for ($n = 3; $n < $limit_sqrt; $n += 2) {
      if ($sieve->get(($n - 3) >> 1) == 0) {
        // Mark all odd multiples of n as non-prime.
        for ($np = ($n * $n); $np < $limit; $np += ($n * 2)) {
          $sieve->set(($np - 3) >> 1, 1);
        }
      }
    }

    $this->limit = $limit;
    $this->sieve = $sieve;
  }

  public function report(int $print = 0) {
    $sieve = $this->sieve;
    $limit = $this->limit;
    $count = $last = 0;
    for ($n = 3; $n < $limit; $n += 2) {
      if ($sieve->get(($n - 3) >> 1) == 0) {
        $count++;
        $last = $n;
        if ($n < $print) {
          echo "$n ";
        }
      }
    }
    echo "\nExpected 664578 primes, last: 9999991";
    echo "\nFound $count primes, last: $last\n";
    return $count;
  }

}

class Sieve {

  private $sieve, $limit;
  
  public function __construct(int $limit) {
    $sieve = array_fill(0, (($limit - 3) >> 1), 0);

    $limit_sqrt = floor(sqrt($limit));
    for ($n = 3; $n < $limit_sqrt; $n += 2) {
      if ($sieve[($n - 3) >> 1] == 0) {
        for ($np = ($n * $n); $np < $limit; $np += ($n * 2)) {
          $sieve[($np - 3) >> 1] = 1;
        }
      }
    }

    $this->limit = $limit;
    $this->sieve = $sieve;
  }

  public function report(int $print = 0) {
    $sieve = $this->sieve;
    $limit = $this->limit;
    $sum = $count = $last = 0;
    for ($n = 3; $n < $limit; $n += 2) {
      if (($sieve[($n - 3) >> 1] ?? 0) == 0) {
        $prime = $n;
        $count++;
        $last = $prime;
        if ($prime < $print) {
          echo "$prime ";
        }
      }
    }
    echo "\nExpected 664578 primes, last: 9999991";
    echo "\nFound $count primes, last: $last\n";
    return $count;
  }
}

$limit=100000000;
$t = microtime(true);
//$sieve = new Sieve($limit);
$sieve = new BitSieve($limit);
$t = microtime(true) - $t;

$count = $sieve->report(1000);
echo round($count / 1000000 / $t, 2) . " million/second\n";

$mem = memory_get_peak_usage(true) >> 20;
print "Peak memory $mem MB\n";
