<?php
/**
 * Utility methods for operating on arrays.
 */
class common_array {
    /**
     * Gets the value of array[key] or default if the key is missing.
     * Use instead of isset($array[key])? $array[key]: null.
     *
     * @param array $array
     * @param scalar $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($array, $key, $default = null) {
        return is_array($array) && isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Gets the value of array[key] or default if the key is empty.
     *
     * @param array $array Set where to look for value.
     * @param scalar $key Key to look for.
     * @param mixed $default Default value to use if array[key] is empty.
     * @return mixed Found value or default.
     */
    public static function get_not_empty($array, $key, $default = null) {
        return is_array($array) && isset($array[$key]) && $array[$key] != null ? $array[$key] : $default;
    }

    /**
     * Gets the value of the first key in $keys that is set in $array, or $default.
     *
     * @param array $array
     * @param array $keys
     * @param mixed $default
     * @return mixed
     */
    public static function get_multi(array $array, array $keys, $default = null) {
        while($key = array_shift($keys)) {
            if(isset($array[$key])) return $array[$key];
        }
        return $default;
    }

    /**
     * Gets the value of the innermost nested key value of $array
     * provided in $keys if it exists, else default
     *
     * @param array $array
     * @param array $keys
     * @param null $default
     * @return mixed
     */
    public static function get_nested($array, $keys, $default = null) {
        if (!is_array($array) || empty($array) || empty($keys)){
            return $default;
        }
        $nextArray = $array;
        foreach ($keys as $key) {
            $nextArray = self::get($nextArray, $key, $default);
        }
        return $nextArray;
    }

    /**
     * Like php's array_map() but the null results are removed and the
     * result is re-indexed to replace missing keys.
     *
     * @param function $callback
     * @param array $array
     * @return array
     */
    public static function map_maybe($callback, array $array) {
        return array_values(array_filter(array_map($callback, $array)));
    }

    /**
     * Apply callback filter on keys of given array and return copy
     * of matching (key, value)s.
     *
     * @param array $array
     * @param int $callback
     * @return array
     */
    public static function filter_keys(array $array, $callback) {

        $keys = array_filter(
            array_keys($array),
            $callback
        );
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Like php's array_map except it applies $callback to the keys of $map.
     *
     * Example: Add 10 to each key:
     * tag_array::key_map(
     *   array(0 => 'a', 1 => 'b'),
     *   function($key) { $key += 10; }
     * ) returns array(10 => 'a', 11 => 'b')
     *
     * @param callback $callback
     * @param array $map
     * @return new map
     */
    public static function key_map(array $map, $callback) {
        $keys = array_keys($map);
        $new_keys = array_map($callback, $keys);
        return array_combine($new_keys, $map);
    }

    /**
     * Takes a map and converts the keys to db bind keys with conventional naming,
     * e.g., array('userId' => 1234) returns array(':user_id' => 1234)
     *
     * @param array $map
     * @return a map of keys to values suitable for OraBindVars()
     */
    public static function make_bind_map(array $map) {
        return self::key_map($map, function($key) {
            return ':' . strtolower(preg_replace('@([A-Z])@', '_\1', $key));
        });
    }

    /**
     * Extract an array of property values
     * @static
     * @param string $key
     * @param array $data
     * @return array
     */
    public static function pluck($key, array $data) {
        // The previous implementation looks great but is hundreds of times slower for large lists!
        // return array_reduce($data, function($result, $array) use($key) {
        //   isset($array[$key]) && $result[] = $array[$key];
        //   return $result;
        // }, array());

        $result = array();
        foreach ($data as $entry) {
            if (isset($entry[$key]))
                $result[] = $entry[$key];
        }
        return $result;
    }

    /**
     * This function is similar to array_map(), except that instead of
     * just applying a callback to each element of the array, the
     * result of the callback is used to partition the array into
     * groups.  Ex:
     *
     *   part(array(1,2,3,4), function($i) { return $i % 2 == 0 ? 'e' : 'o' })
     *
     * will return:
     *
     *   array('o'=>array(1,3), 'e'=>array(2,4))
     */

    public static function part($callback, array $arr) {
        $partition = array();
        foreach ($arr as $a) {
            $idx = call_user_func($callback, $a);
            if (! isset($partition[$idx])) {
                $partition[$idx] = array($a);
            } else {
                $partition[$idx][] = $a;
            }
        }
        return $partition;
    }

    /**
     * This is like part(), except only sequential items are grouped
     * together.  Ex:
     *
     *   part_seq(array(1,3,2,5,6,8), function($i) { return $i % 2 == 0 ? 'e' : 'o' })
     *
     * will return:
     *
     *   array(
     *       array('o',array(1,3)),
     *       array('e',array(2)),
     *       array('o',array(5)),
     *       array('e',array(6,8)))
     */
    public static function part_seq($callback, array $arr) {
        $partition = array();
        $empty = true;
        $current = null;
        foreach ($arr as $a) {
            $idx = call_user_func($callback, $a);
            if ($empty) {
                $empty = false;
                $current = array($idx, array($a));
            } else if ($idx !== $current[0]) {
                $partition[] = $current;
                $current = array($idx, array($a));
            } else {
                $current[1][] = $a;
            }
        }
        if (! $empty) {
            $partition[] = $current;
        }
        return $partition;
    }

    /**
     * Like php's array_reduce but callback is a function of key, value,
     * and accumulator. In other words, array keys are available to the callback.
     * @param array $array input to act on
     * @param callback $funcname callback of key, value, accumulator
     * @param array $initial (optional) starting data
     */
    function reduce($array, $funcname, $initial = array()) {
        $acc = $initial;
        array_walk($array, function($value, $key) use ($funcname, &$acc) {
            $acc = $funcname($key, $value, $acc);
        });
        return $acc;
    }

    /**
     *  Helper function to turn key-value array into a list of named pairs. Convert from
     *  e.g. internal inventory format to the format desired by API clients
     *
     *  @param $arr - Array of key-value pairs, for example [ a => m, c => n, ... ]
     *  @param $key - 'id' in the example here (see return value)
     *  @param $value - 'count' in the example here (see return value)
     *  @return Array in the format [ [ id => a, count => m ], [ id => c, count => n ], ... ]
     */
    static public function kv_to_pairs($arr, $key, $value) {
        $result = array();
        foreach($arr as $k => $v) {
            $result[] = array($key => $k, $value => $v);
        }
        return $result;
    }

    /**
     * Sort a sequential array of elements by comparing the values returned by keyfunc.
     */
    public static function sort_keyfunc(&$array, $keyfunc) {
        $keys = array_map($keyfunc, $array); // get the keys for each item
        array_multisort($keys, $array); // sort $array according to the sorted keys
        return $array;
    }

    /**
     * Flatten--just once--an array.
     *
     * @param $array
     * @return array
     */
    public static function flatten(array $array) {
        return array_reduce(
            $array,
            function($agg, $item) {
                return array_merge($agg, (array) $item);
            },
            array()
        );
    }

    /**
     * Recursively merge two arrays while only allowing a distinct key's value
     * @static
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function merge_recursive_distinct (array &$array1, array &$array2) {
        $merged = $array1;

        foreach ( $array2 as $key => &$value ) {
            if (is_array($value) && isset ($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    public static function explode_ints($s) {
        return array_map('intval', array_map('trim', explode(',', $s)));
    }



    public static function unique_docs_vector($docs){
        $newArray = array();
        $seenMap = array();
        foreach ($docs as $doc) {
            $id = (int)$doc['id'];
            if (isset($seenMap[$id])) continue;
            $doc['id'] = $id;
            $seenMap[$id] = true;
            $newArray[] = $doc;
        }
        return $newArray;
    }

    /* Add a key to an arrray only if it's not already added.
     * Returns true if the item was added, false otherwise.
     */
    public static function add_distinct($array, $key, $value) {
        if (!isset($array[$key])) {
            $array[$key] = $value;
            return true;
        }
        return false;
    }

    public static function renameKeys($array, $renameFunc) {
        $retArr = array();

        array_walk($array,function($value, $key) use(&$retArr, $renameFunc) {
            $newKey = call_user_func($renameFunc, $key);
            $retArr[$newKey] = $value;
        });

        return $retArr;
    }

    // Provides a recursive version of array_diff that allows for elements that are themselves arrays.
    public static function array_recursive_diff($aArray1, $aArray2) {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = self::array_recursive_diff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }

    public static function is_associative($data){
        return array_keys($data) !== range(0, count($data) - 1);
    }

    public static function substr_in_array($needle, array $haystack) {
        foreach($haystack as $pos => $hay) {
            if(strpos($hay, $needle) !== false) {
                return $pos;
            }
        }

        return false;
    }
}
