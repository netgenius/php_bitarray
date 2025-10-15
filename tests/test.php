<?php
$b = new BitArray(10);
$b->set(3, true);
$b->set(5, true);

for ($i = 0; $i < 10; $i++) {
    echo "$i: ", $b->get($i) ? '1' : '0', "\n";
}
