#ifndef PHP_BITARRAY_H
#define PHP_BITARRAY_H

#include "php.h"
#include <stdint.h>

extern zend_module_entry bitarray_module_entry;
#define phpext_bitarray_ptr &bitarray_module_entry
#define PHP_BITARRAY_VERSION "0.1"

// ------------------------
// BitArray object struct
// ------------------------
typedef uint64_t bitarray_bits_t;

typedef struct _bitarray_object {
    size_t size;               // number of bits
    bitarray_bits_t *data;     // storage as integers
    zend_object std;
} bitarray_object;

// Helper macro to fetch the C object from a Zend object
static inline bitarray_object *php_bitarray_fetch_object(zend_object *obj) {
    return (bitarray_object *)((char *)(obj) - XtOffsetOf(bitarray_object, std));
}
#define Z_BITARRAY_P(zv) php_bitarray_fetch_object(Z_OBJ_P((zv)))

#endif /* PHP_BITARRAY_H */
