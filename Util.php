<?php

namespace Brouzie\Sphinxy;

use Brouzie\Sphinxy\Exception\ExtraParametersException;
use Brouzie\Sphinxy\Exception\ParameterNotFoundException;

class Util
{
    /**
     * This code ported from https://github.com/auraphp/Aura.Sql/blob/master/src/Aura/Sql/Connection/AbstractConnection.php
     *
     * @param $query
     * @param $params
     *
     * @license http://opensource.org/licenses/bsd-license.php BSD
     *
     * @return string
     */
    public static function prepareQuery($query, $params, Escaper $escaper)
    {
        // find all text parts not inside quotes or backslashed-quotes
        $apos = "'";
        $quot = '"';
        $parts = preg_split(
            "/(($apos+|$quot+|\\$apos+|\\$quot+).*?)\\2/m",
            $query,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $usedParams = array();
        // loop through the non-quoted parts (0, 3, 6, 9, etc.)
        for ($i = 0, $k = count($parts); $i <= $k; $i += 3) {
            // get the part as a reference so it can be modified in place
            $part =& $parts[$i];

            // find all :placeholder matches in the part
            preg_match_all(
                "/\W:([a-zA-Z_][a-zA-Z0-9_]*)/m",
                $part . PHP_EOL,
                $matches
            );

            // for each of the :placeholder matches ...
            foreach ($matches[1] as $key) {
                if (!array_key_exists($key, $params)) {
                    throw new ParameterNotFoundException(sprintf('The parameter "%s" not found.', $key));
                }

                $find = "/(^|\W)(:$key)(\W|$)/m";
                $repl = '${1}'.addcslashes($escaper->quote($params[$key]), '\\').'${3}';
                $part = preg_replace($find, $repl, $part);
                $usedParams[$key] = true;
            }
        }

        if (count($params) > count($usedParams)) {
            throw new ExtraParametersException(sprintf(
                'Extra parameters found: %s.',
                implode(', ', array_keys(array_diff_key($params, $usedParams)))
            ));
        }

        return implode('', $parts);
    }
}
