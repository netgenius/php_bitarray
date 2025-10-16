#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_bitarray.h"
#include "Zend/zend_exceptions.h"

zend_class_entry *bitarray_ce;
static zend_object_handlers bitarray_object_handlers;

#define NUM_BITS (sizeof(bitarray_bits_t) * 8)

// -------------------------------
// Internal helpers
// -------------------------------
static void bitarray_set_bit(bitarray_object *obj, size_t index, zend_bool value)
{
    size_t int_index = index / NUM_BITS;
    bitarray_bits_t bit_mask = (bitarray_bits_t) 1 << (index % NUM_BITS);

    if (value) {
        obj->data[int_index] |= bit_mask;
    } else {
        obj->data[int_index] &= ~bit_mask;
    }
}

static zend_bool bitarray_get_bit(bitarray_object *obj, size_t index)
{
    size_t int_index = index / NUM_BITS;
    bitarray_bits_t bit_mask = (bitarray_bits_t) 1 << (index % NUM_BITS);
    return (obj->data[int_index] & bit_mask) != 0;
}

// -------------------------------
// Object handlers
// -------------------------------
static void bitarray_free_obj(zend_object *object)
{
    bitarray_object *obj = php_bitarray_fetch_object(object);
    if (obj->data) {
        efree(obj->data);
    }
    zend_object_std_dtor(object);
}

static zend_object *bitarray_create_obj(zend_class_entry *ce)
{
    bitarray_object *obj = zend_object_alloc(sizeof(bitarray_object), ce);
    obj->data = NULL;
    obj->size = 0;

    zend_object_std_init(&obj->std, ce);
    object_properties_init(&obj->std, ce);

    obj->std.handlers = &bitarray_object_handlers;
    return &obj->std;
}

// -------------------------------
// ArrayAccess handlers
// -------------------------------
static zval *bitarray_read_dimension(zend_object *object, zval *offset, int type, zval *rv)
{
    bitarray_object *obj = php_bitarray_fetch_object(object);
    zend_long index = zval_get_long(offset);

    if (index < 0 || (size_t)index >= obj->size) {
        zend_throw_exception(NULL, "Index out of range", 0);
        return NULL;
    }

    ZVAL_BOOL(rv, bitarray_get_bit(obj, index));
    return rv;
}

static void bitarray_write_dimension(zend_object *object, zval *offset, zval *value)
{
    bitarray_object *obj = php_bitarray_fetch_object(object);
    zend_long index = zval_get_long(offset);
    zend_bool val = zend_is_true(value);

    if (index < 0 || (size_t)index >= obj->size) {
        zend_throw_exception(NULL, "Index out of range", 0);
        return;
    }

    bitarray_set_bit(obj, index, val);
}

// -------------------------------
// PHP Methods
// -------------------------------
PHP_METHOD(BitArray, __construct)
{
    zend_long size;

    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_LONG(size)
    ZEND_PARSE_PARAMETERS_END();

    if (size <= 0) {
        zend_throw_exception(NULL, "Size must be greater than 0", 0);
        RETURN_THROWS();
    }

    bitarray_object *obj = Z_BITARRAY_P(getThis());
    obj->size = (size_t)size;
    size_t num_ints = (obj->size + NUM_BITS - 1) / NUM_BITS;
    obj->data = ecalloc(num_ints, sizeof(bitarray_bits_t));
}

// -------------------------------
// Arginfo
// -------------------------------
ZEND_BEGIN_ARG_INFO_EX(arginfo_bitarray_construct, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, size, IS_LONG, 0)
ZEND_END_ARG_INFO()

// -------------------------------
// Method entries
// -------------------------------
static const zend_function_entry bitarray_methods[] = {
    PHP_ME(BitArray, __construct, arginfo_bitarray_construct, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_FE_END
};

// -------------------------------
// Module init
// -------------------------------
PHP_MINIT_FUNCTION(bitarray)
{
    zend_class_entry ce;
    INIT_CLASS_ENTRY(ce, "BitArray", bitarray_methods);
    bitarray_ce = zend_register_internal_class(&ce);
    bitarray_ce->create_object = bitarray_create_obj;

    memcpy(&bitarray_object_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    bitarray_object_handlers.offset = XtOffsetOf(bitarray_object, std);
    bitarray_object_handlers.free_obj        = bitarray_free_obj;

    // ArrayAccess handlers
    bitarray_object_handlers.read_dimension  = bitarray_read_dimension;
    bitarray_object_handlers.write_dimension = bitarray_write_dimension;
    bitarray_object_handlers.has_dimension   = NULL;
    bitarray_object_handlers.unset_dimension = NULL;

    return SUCCESS;
}

// -------------------------------
// Module entry
// -------------------------------
zend_module_entry bitarray_module_entry = {
    STANDARD_MODULE_HEADER,
    "bitarray",
    NULL,
    PHP_MINIT(bitarray),
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_BITARRAY_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_BITARRAY
# ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
# endif
ZEND_GET_MODULE(bitarray)
#endif
