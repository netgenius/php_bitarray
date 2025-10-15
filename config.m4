PHP_ARG_ENABLE(bitarray, whether to enable BitArray support,
[  --enable-bitarray   Enable BitArray support])

if test "$PHP_BITARRAY" != "no"; then
  PHP_NEW_EXTENSION(bitarray, bitarray.c, $ext_shared)
fi
