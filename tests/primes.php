<?php

class Sieve {

  private $sieve, $limit;
  
  public function __construct(int $limit) {
    //$sieve = array_fill(0, (($limit - 3) >> 1), 0);
    //$sieve = str_repeat("0", (($limit - 3) >> 1));
    $sieve = new BitArray(($limit - 2) >> 1);

    $limit_sqrt = floor(sqrt($limit));
    for ($n = 3; $n < $limit_sqrt; $n += 2) {
      if (($sieve[($n - 3) >> 1] ?? 0) == 0) {
        if (0) {
          for ($np = ($n * $n); $np < $limit; $np += ($n * 2)) {
            $sieve[($np - 3) >> 1] = 1;
          }
        }
        else {
          // Flag the non-primes.
          $np_first = (($n * $n) - 3) >> 1;
          $np_last = ($limit - 4) >> 1;
          for (; $np_first <= $np_last; $np_first += $n) {
            $sieve[$np_first] = 1;
          }
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
        $sum += $prime;
        $last = $prime;
        if ($prime < $print) {
          echo "$prime ";
        }
      }
    }

    echo "\nFound $count primes, last: $last, sum: $sum\n";
    if ($limit == 100000000) {
      if ($sum != 279209790387274 || $count != 5761454 || $last != 99999989) {
        throw new Exception("Sieve error");
      }
    } 
    return $count;
  }
}

$limit=100000000;
$t = microtime(true);
$sieve = new Sieve($limit);
//$sieve = new BitSieve($limit);
$t = microtime(true) - $t;

$count = $sieve->report(1000);
echo round($count / 1000000 / $t, 2) . " million/second\n";

$mem = memory_get_peak_usage(true) >> 20;
print "Peak memory $mem MB\n";
