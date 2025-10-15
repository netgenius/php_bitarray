#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_bitarray.h"
#include "Zend/zend_exceptions.h"

zend_class_entry *bitarray_ce;
static zend_object_handlers bitarray_object_handlers;

// -------------------------------
// Object handlers
// -------------------------------
static void bitarray_free_obj(zend_object *object)
{
    bitarray_object *obj = php_bitarray_fetch_object(object); // <- correct
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
// BitArray::__construct(size)
// -------------------------------
PHP_METHOD(BitArray, __construct)
{
    zend_long size;

    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_LONG(size)
    ZEND_PARSE_PARAMETERS_END();

    if (size <= 0) {
        zend_throw_exception(NULL, "Size must be positive", 0);
        RETURN_THROWS();
    }

    bitarray_object *obj = Z_BITARRAY_P(getThis());
    obj->size = (size_t) size;
    size_t num_ints = (obj->size + 31) / 32;
    obj->data = ecalloc(num_ints, sizeof(uint32_t));
}

// -------------------------------
// BitArray::set(index, value)
// -------------------------------
PHP_METHOD(BitArray, set)
{
    zend_long index;
    zend_bool value;

    ZEND_PARSE_PARAMETERS_START(2, 2)
        Z_PARAM_LONG(index)
        Z_PARAM_BOOL(value)
    ZEND_PARSE_PARAMETERS_END();

    bitarray_object *obj = Z_BITARRAY_P(getThis());

    if (index < 0 || (size_t) index >= obj->size) {
        zend_throw_exception(NULL, "Index out of range", 0);
        RETURN_THROWS();
    }

    size_t int_index = index / 32;
    uint32_t bit_mask = 1u << (index % 32);

    if (value) {
        obj->data[int_index] |= bit_mask;
    } else {
        obj->data[int_index] &= ~bit_mask;
    }
}

// -------------------------------
// BitArray::get(index) -> bool
// -------------------------------
PHP_METHOD(BitArray, get)
{
    zend_long index;

    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_LONG(index)
    ZEND_PARSE_PARAMETERS_END();

    bitarray_object *obj = Z_BITARRAY_P(getThis());

    if (index < 0 || (size_t) index >= obj->size) {
        zend_throw_exception(NULL, "Index out of range", 0);
        RETURN_THROWS();
    }

    size_t int_index = index / 32;
    uint32_t bit_mask = 1u << (index % 32);

    RETURN_BOOL((obj->data[int_index] & bit_mask) != 0);
}

// -------------------------------
// Arginfo
// -------------------------------
ZEND_BEGIN_ARG_INFO_EX(arginfo_bitarray_construct, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, size, IS_LONG, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_bitarray_set, 0, 0, 2)
    ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
    ZEND_ARG_TYPE_INFO(0, value, _IS_BOOL, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_bitarray_get, 0, 0, 1)
    ZEND_ARG_TYPE_INFO(0, index, IS_LONG, 0)
ZEND_END_ARG_INFO()

// -------------------------------
// Method entries
// -------------------------------
static const zend_function_entry bitarray_methods[] = {
    PHP_ME(BitArray, __construct, arginfo_bitarray_construct, ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
    PHP_ME(BitArray, set,         arginfo_bitarray_set,       ZEND_ACC_PUBLIC)
    PHP_ME(BitArray, get,         arginfo_bitarray_get,       ZEND_ACC_PUBLIC)
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
    bitarray_object_handlers.free_obj = bitarray_free_obj;

    return SUCCESS;
}

// -------------------------------
// Module entry
// -------------------------------
zend_module_entry bitarray_module_entry = {
    STANDARD_MODULE_HEADER,
    "bitarray",
    NULL, // no global functions
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
