<?php

namespace Brouzie\Sphinxy;

use Brouzie\Sphinxy\Connection\ConnectionInterface;

class Escaper
{
    /**
     * @var ConnectionInterface
     */
    protected $conn;

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Wraps the input with identifiers when necessary.
     *
     * @param Expr|string $value The string to be quoted, or an Expression to leave it untouched
     *
     * @return string The untouched Expression or the quoted string
     */
    public function quoteIdentifier($value)
    {
        if ($value instanceof Expr) {
            return $value->getValue();
        }

        if ($value === '*') {
            return $value;
        }

        $pieces = explode('.', $value);

        foreach ($pieces as $key => $piece) {
            $pieces[$key] = '`'.$piece.'`';
        }

        return implode('.', $pieces);
    }

    /**
     * Calls $this->quoteIdentifier() on every element of the array passed.
     *
     * @param array $array An array of strings to be quoted
     *
     * @return array The array of quoted strings
     */
    public function quoteIdentifierArr(array $array)
    {
        $result = array();

        foreach ($array as $key => $item) {
            $result[$key] = $this->quoteIdentifier($item);
        }

        return $result;
    }

    /**
     * Adds quotes around values when necessary.
     * Based on FuelPHP's quoting function.
     *
     * @param Expr|string $value The input string, eventually wrapped in an expression to leave it untouched
     *
     * @return string The untouched Expression or the quoted string
     */
    public function quote($value)
    {
        switch (true) {
            case $value === null:
                return 'null';

            case $value === true:
                return '1';

            case $value === false:
                return '0';

            case $value instanceof Expr:
                return $value->getValue();

            case is_int($value) || ctype_digit($value):
                return (int)$value;

            case is_float($value):
                // Convert to non-locale aware float to prevent possible commas
                return sprintf('%F', $value);

            case is_array($value):
                // Supports MVA attributes
                if (!count($value)) {
                    return '()';
                }

                return '('.implode(',', $this->quoteArr($value)).')';
        }

        return $this->conn->quote($value);
    }

    /**
     * Calls $this->quote() on every element of the array passed.
     *
     * @param array $array The array of strings to quote
     *
     * @return array The array of quotes strings
     */
    public function quoteArr(array $array)
    {
        $result = array();

        foreach ($array as $key => $item) {
            $result[$key] = $this->quote($item);
        }

        return $result;
    }

    public function quoteSetArr(array $array)
    {
        $result = array();

        foreach ($array as $key => $item) {
            $result[$this->quoteIdentifier($key)] = $this->quote($item);
        }

        return $result;
    }


    /**
     * Escapes the query for the MATCH() function
     *
     * @param  string $string The string to escape for the MATCH
     *
     * @return  string  The escaped string
     */
    public function escapeMatch($string)
    {
        $from = array('\\', '(', ')', '|', '-', '!', '@', '~', '"', '&', '/', '^', '$', '=');
        $to = array('\\\\', '\(', '\)', '\|', '\-', '\!', '\@', '\~', '\"', '\&', '\/', '\^', '\$', '\=');

        return str_replace($from, $to, $string);
    }

    /**
     * Escapes the query for the MATCH() function
     * Allows some of the control characters to pass through for use with a search field: -, |, "
     * It also does some tricks to wrap/unwrap within " the string and prevents errors
     *
     * @param  string $string The string to escape for the MATCH
     *
     * @return  string  The escaped string
     */
    public function halfEscapeMatch($string)
    {
        $fromTo = array(
            '\\' => '\\\\',
            '(' => '\(',
            ')' => '\)',
            '!' => '\!',
            '@' => '\@',
            '~' => '\~',
            '&' => '\&',
            '/' => '\/',
            '^' => '\^',
            '$' => '\$',
            '=' => '\=',
        );

        $string = str_replace(array_keys($fromTo), array_values($fromTo), $string);

        // this manages to lower the error rate by a lot
        if (mb_substr_count($string, '"') % 2 !== 0) {
            $string .= '"';
        }

        $fromToPreg = array(
            "'\"([^\s]+)-([^\s]*)\"'" => "\\1\-\\2",
            "'([^\s]+)-([^\s]*)'" => "\"\\1\-\\2\""
        );

        $string = preg_replace(array_keys($fromToPreg), array_values($fromToPreg), $string);

        return $string;
    }
}
