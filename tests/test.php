<?php
$b = new BitArray(10);
$b[3] = true;
$b[5] = true;

for ($i = 0; $i < 10; $i++) {
  echo "$i: ", $b[$i] ? '1' : '0', "\n";
}
