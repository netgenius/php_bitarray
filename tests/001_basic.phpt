--TEST--
Basic BitArray construction
--SKIPIF--
<?php
if (!extension_loaded('bitarray')) die("skip: bitarray not loaded\n");
?>
--FILE--
<?php
$b = new BitArray(8);
var_dump($b instanceof BitArray);
?>
--EXPECT--
bool(true)
