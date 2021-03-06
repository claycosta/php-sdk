<?php namespace Konduto\Models;

const BOOL   = 'boolean';
const INT    = 'integer';
const STRING = 'string';
const FLOAT  = 'double';

const IDX_TYPE    = 0;
const IDX_MIN     = 1;
const IDX_MAX     = 2;
const IDX_PATTERN = 3;

const REGEX_LETTERS_DIGITS = "/^[a-zA-Z0-9-_]+\z/";
const REGEX_LETTERS        = "/^[a-zA-Z]+\z/";
const REGEX_DIGITS         = "/^[0-9]+\z/";
const REGEX_HEXA_DIGITS    = "/^[a-fA-F0-9]+\z/";
const REGEX_FULL_DATE      = "/^\d{4}-\d{2}-\d{2}\z/";
const REGEX_IPv4           = "/^(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?|[0-9])\z/";
const REGEX_CREDIT         = "/^credit\z/";
const REGEX_BOLETO         = "/^boleto\z/";

abstract class ValidationSchema {
    
    /**
     * This structure $validation follows the pattern:
     * object => [
     *      field => [type, minimum, maximum, pattern],
     *      field => ...
     * ]
     *
     * 'type' accepts one of the constants defined above: BOOL, INT, STRING and FLOAT.
     * For type STRING, the 2nd and 3rd positions, 'minimum' and 'maximum', refer to the number of character.
     * For types INT and FLOAT, the 2nd and 3rd positions, 'minimum' and 'maximum', refer to minimum and max numbers allowed.
     * For type BOOL, positions 'minimum' and 'maximum' are not used.
     * Optionally, it could have a IDX_PATTERN key containing a regex expression to be applied.
     */
    private static $validation = [
        "order" => [
            "id"              => [STRING,  1,     100, REGEX_LETTERS_DIGITS],
            "visitor"         => [STRING, 40,      40, REGEX_LETTERS_DIGITS],
            "total_amount"    => [ FLOAT,  0, 9999999],
            "shipping_amount" => [ FLOAT,  0, 9999999],
            "tax_amount"      => [ FLOAT,  0, 9999999],
            "currency"        => [STRING,  3,       3],
            "installments"    => [   INT,  1,     999],
            "ip"              => [STRING,  7,      15, REGEX_IPv4]
        ],
        "customer" => [
            "id"              => [STRING,  1,     100],
            "name"            => [STRING,  0,     100],
            "tax_id"          => [STRING,  0,     100],
            "phone1"          => [STRING,  0,     100],
            "phone2"          => [STRING,  0,     100],
            "email"           => [STRING,  0,     100],
            "new"             => [  BOOL], 
            "vip"             => [  BOOL]
        ],
        "address" => [
            "name"            => [STRING,  0,     100],
            "address1"        => [STRING,  0,     255],
            "address2"        => [STRING,  0,     255],
            "city"            => [STRING,  0,     100],
            "state"           => [STRING,  0,     100],
            "zip"             => [STRING,  0,     100],
            "country"         => [STRING,  2,       2, REGEX_LETTERS]
        ],
        "credit_card" => [
            "type"            => [STRING,  0,       8, REGEX_CREDIT],
            "status"          => [STRING,  0,       8],
            "sha1"            => [STRING, 40,      40, REGEX_HEXA_DIGITS],
            "bin"             => [STRING,  6,       6, REGEX_DIGITS],
            "last4"           => [STRING,  4,       4, REGEX_DIGITS],
            "expiration_date" => [STRING,  6,       6, REGEX_DIGITS]
        ],
        "boleto" => [
            "type"            => [STRING,  0,       8, REGEX_BOLETO],
            "expiration_date" => [STRING, 10,      10, REGEX_FULL_DATE]
        ],
        "item" => [
            "sku"             => [STRING,  0,     100],
            "product_code"    => [STRING,  0,     100],
            "category"        => [   INT,  0,    9999],
            "name"            => [STRING,  0,     100],
            "description"     => [STRING,  0,     100],
            "unit_cost"       => [ FLOAT,  0, 9999999],
            "quantity"        => [   INT,  0, 9999999],
            "discount"        => [ FLOAT,  0, 9999999]
        ]
    ];

    /**
     * Validates whether a field is valid according to $validation structure. 
     * Converts $var to the correct type if possible.
     */
    public static function validateField($object, $field, &$var) {

        $isValid = false;

        switch (self::$validation[$object][$field][IDX_TYPE]) {

            case INT:
                // If $var is a string containing an int, convert $val to int
                if ((gettype($var) == STRING and ctype_digit($var)) or gettype($var) == FLOAT) {
                    $var = intval($var);
                }
                $isValid = is_int($var) && self::validateNumberLength($object, $field, $var);
                break;

            case STRING:
                // If $var is a string containing an int, convert $val to int
                if (gettype($var) == INT or gettype($var) == FLOAT) {
                    $var = strval($var);
                }
                $isValid = is_string($var) && self::validateStringLength($object, $field, $var);
                break;

            case FLOAT:
                // If $var is convertible to float, converts it.
                if (!is_float($var) and is_numeric($var)) {
                    $var = floatval($var);
                }
                $isValid = is_float($var) && self::validateNumberLength($object, $field, $var);
                break;

            case BOOL:
                // If $var is 0, 1, '0' or '1', converts to bool.
                if (is_numeric($var) and ($var == 1 or $var == 0)) {
                    $var = boolval($var);
                }
                // If $var is a string 'true' or 'false', case insensitive, converts to bool.
                if (is_string($var) and (strcasecmp('true', $var) or strcasecmp('false', $var))) {
                    $var = (strcasecmp('true', $var) == 0);
                }
                $isValid = is_bool($var);
                break;
        }

        // Validate regex pattern, if present
        if ($isValid and array_key_exists(IDX_PATTERN, self::$validation[$object][$field])) {
            $isValid &= preg_match(self::$validation[$object][$field][IDX_PATTERN], $var) == 1;
        }

        return $isValid;
    }

    public static function validateNumberLength($object, $field, $number) {
        // Assumes $number is numeric!
        return $number >= self::$validation[$object][$field][IDX_MIN] && $number <= self::$validation[$object][$field][IDX_MAX];
    }


    public static function validateStringLength($object, $field, $string) {
        // Assumes $string is string!
        return strlen($string) >= self::$validation[$object][$field][IDX_MIN] && strlen($string) <= self::$validation[$object][$field][IDX_MAX];
    }
}