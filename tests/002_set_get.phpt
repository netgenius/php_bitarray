--TEST--
Set and get bits
--SKIPIF--
<?php
if (!extension_loaded('bitarray')) die("skip: bitarray not loaded\n");
?>
--FILE--
<?php
$b = new BitArray(10);
$b->set(3, true);
$b->set(5, true);

for ($i = 0; $i < 10; $i++) {
    echo $b->get($i) ? "1\n" : "0\n";
}
?>
--EXPECT--
0
0
0
1
0
1
0
0
0
0
