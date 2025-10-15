
# PHP bitarray

### Build, test and install
`
make clean && phpize \
&& EXTRA_CFLAGS="-O2 -march=native -flto" ./configure --enable-bitarray \
&& make \
&& echo "Running sudo make install:" && sudo make install \
&& make test TESTS=tests/*.phpt
`
