
# PHP bitarray

### Build, test and install

    make clean && phpize \
    && CFLAGS='-O2' ./configure --enable-bitarray \
    && make \
    && echo "Running sudo make install:" && sudo make install \
    && make test TESTS=tests/*.phpt

    
    make clean && phpize && CFLAGS='-O2' ./configure --enable-bitarray && make && echo "Running sudo make install:" && sudo make install && make test TESTS=tests/*.phpt

