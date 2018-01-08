<?php

/**
 * PHP函数扩展
 * @author  Zou Yiliang <it9981@gmail.com>
 * @since   1.0
 */

if (!function_exists('array_column')) { // PHP < 5.5
    /**
     * 返回数组中指定的一列
     * 返回input数组中键值为column_key的列， 如果指定了可选参数index_key，那么input数组中的这一列的值将作为返回数组中对应值的键。
     * $records = array(
     *        array(
     *            'id' => 2135,
     *            'first_name' => 'John',
     *            'last_name' => 'Doe',
     *        ),
     *        array(
     *            'id' => 3245,
     *            'first_name' => 'Sally',
     *            'last_name' => 'Smith',
     *        ),
     * );
     * $first_names = array_column($records, 'first_name');
     * //Array ( [0] => John [1] => Sally )
     *
     * $last_names = array_column($records, 'last_name', 'id');
     * //Array ( [2135] => Doe [3245] => Smith )
     *
     * @param array $input 需要取出数组列的多维数组（或结果集）
     * @param mixed $columnKey 需要返回值的列，它可以是索引数组的列索引，或者是关联数组的列的键。 也可以是NULL，此时将返回整个数组（配合index_key参数来重置数组键的时候，非常管用）
     * @param mixed $indexKey 作为返回数组的索引/键的列，它可以是该列的整数索引，或者字符串键值。
     * @return array 从多维数组中返回单列数组
     */
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        foreach ($input as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $value = is_null($columnKey) ? $row : $row[$columnKey];
            if (is_null($indexKey)) {
                $result[] = $value;
            } else {
                $result[$row[$indexKey]] = $value;
            }
        }
        return $result;
    }
}

if (!function_exists('lcfirst')) { // PHP < 5.3.0
    /**
     * 使一个字符串的第一个字符小写
     * @param string $str 输入的字符串
     * @return string 返回转换后的字符串。
     */
    function lcfirst($str)
    {
        if (strlen($str) > 0) {
            $str[0] = strtolower($str[0]);
        }
        return $str;
    }
}

if (!function_exists('array_replace')) { // PHP 5 < 5.3.0
    function array_replace()
    {
        $args = func_get_args();
        $num_args = func_num_args();
        $res = array();
        for ($i = 0; $i < $num_args; $i++) {
            if (is_array($args[$i])) {
                foreach ($args[$i] as $key => $val) {
                    $res[$key] = $val;
                }
            } else {
                trigger_error(__FUNCTION__ . '(): Argument #' . ($i + 1) . ' is not an array', E_USER_WARNING);
                return NULL;
            }
        }
        return $res;
    }
}

if (!function_exists('array_replace_recursive')) { // PHP 5 < 5.3.0
    function array_replace_recursive($base, $replacements)
    {
        foreach (array_slice(func_get_args(), 1) as $replacements) {
            $bref_stack = array(&$base);
            $head_stack = array($replacements);

            do {
                end($bref_stack);

                $bref = &$bref_stack[key($bref_stack)];
                $head = array_pop($head_stack);

                unset($bref_stack[key($bref_stack)]);

                foreach (array_keys($head) as $key) {
                    if (isset($key, $bref) && is_array($bref[$key]) && is_array($head[$key])) {
                        $bref_stack[] = &$bref[$key];
                        $head_stack[] = $head[$key];
                    } else {
                        $bref[$key] = $head[$key];
                    }
                }
            } while (count($head_stack));
        }

        return $base;
    }
}

if (!function_exists('hash_equals')) { // PHP 5 < 5.6.0

    /**
     * 可防止时序攻击的字符串比较
     * 比较两个字符串，无论它们是否相等，本函数的时间消耗是恒定的。
     * @param $knownString
     * @param $userInput
     * @return bool
     */
    function hash_equals($knownString, $userInput)
    {
        $knownString = (string)$knownString;
        $userInput = (string)$userInput;

        $knownLen = strlen($knownString);
        $userLen = strlen($userInput);

        // Extend the known string to avoid uninitialized string offsets
        $knownString .= $userInput;

        // Set the result to the difference between the lengths
        $result = $knownLen - $userLen;

        // Note that we ALWAYS iterate over the user-supplied length
        // This is to mitigate leaking length information
        for ($i = 0; $i < $userLen; $i++) {
            $result |= (ord($knownString[$i]) ^ ord($userInput[$i]));
        }

        // They are only identical strings if $result is exactly 0...
        return 0 === $result;
    }
}

if (!function_exists('imageflip')) { //PHP < 5.5.0

    defined('IMG_FLIP_HORIZONTAL') or define('IMG_FLIP_HORIZONTAL', 0);
    defined('IMG_FLIP_VERTICAL') or define('IMG_FLIP_VERTICAL', 1);
    defined('IMG_FLIP_BOTH') or define('IMG_FLIP_BOTH', 2);

    function imageflip($image, $mode)
    {
        switch ($mode) {
            case IMG_FLIP_HORIZONTAL:
                $max_x = imagesx($image) - 1;
                $half_x = $max_x / 2;
                $sy = imagesy($image);
                $temp_image = imageistruecolor($image) ? imagecreatetruecolor(1, $sy) : imagecreate(1, $sy);
                for ($x = 0; $x < $half_x; ++$x) {
                    imagecopy($temp_image, $image, 0, 0, $x, 0, 1, $sy);
                    imagecopy($image, $image, $x, 0, $max_x - $x, 0, 1, $sy);
                    imagecopy($image, $temp_image, $max_x - $x, 0, 0, 0, 1, $sy);
                }
                break;
            case IMG_FLIP_VERTICAL:
                $sx = imagesx($image);
                $max_y = imagesy($image) - 1;
                $half_y = $max_y / 2;
                $temp_image = imageistruecolor($image) ? imagecreatetruecolor($sx, 1) : imagecreate($sx, 1);
                for ($y = 0; $y < $half_y; ++$y) {
                    imagecopy($temp_image, $image, 0, 0, 0, $y, $sx, 1);
                    imagecopy($image, $image, 0, $y, 0, $max_y - $y, $sx, 1);
                    imagecopy($image, $temp_image, 0, $max_y - $y, 0, 0, $sx, 1);
                }
                break;
            case IMG_FLIP_BOTH:
                $sx = imagesx($image);
                $sy = imagesy($image);
                $temp_image = imagerotate($image, 180, 0);
                imagecopy($image, $temp_image, 0, 0, 0, 0, $sx, $sy);
                break;
            default:
                return;
        }
        imagedestroy($temp_image);
    }
}

//打印变量
//if (!function_exists('dump')) {
//    function dump($arg, $return = false, $layer = 1)
//    {
//        $html = '';
//
//        //字符串
//        if (is_string($arg)) {
//            $len = strlen($arg);
//            $html .= "<small>string</small> <font color='#cc0000'>'{$arg}'</font>(length={$len})";
//        } else if (is_float($arg)) {
//            $html .= "<small>float</small> <font color='#f57900'>{$arg}</font>";
//        } //布尔
//        else if (is_bool($arg)) {
//            $html .= "<small>boolean</small> <font color='#75507b'>" . ($arg ? 'true' : 'false') . "</font>";
//        } //null
//        else if (is_null($arg)) {
//            $html .= "<font color='#3465a4'>null</font>";
//        } //资源
//        else if (is_resource($arg)) {
//            $type = get_resource_type($arg);
//            $html .= "<small>resource</small>(<i>{$type}</i>)";
//        } //整型
//        else if (is_int($arg)) {
//            $html .= "<small>int</small> <font color='#4e9a06'>" . $arg . "</font>";
//        } //数组
//        else if (is_array($arg)) {
//            $count = count($arg);
//            $html .= "<b>array</b> (size={$count})";
//            if (count($arg) == 0) {
//                $html .= "\n" . str_pad(' ', $layer * 4) . "empty";
//            }
//
//            foreach ($arg as $key => $value) {
//                $html .= "\n" . str_pad(' ', $layer * 4) . "'{$key}' => ";
//                $html .= dump($value, true, $layer + 1);
//            }
//        } //对象
//        else if (is_object($arg)) {
//
//            ob_start();
//            var_dump($arg);
//            $html .= ob_get_clean();
//
//        } //未知
//        else {
//            ob_start();
//            var_dump($arg);
//            $html .= ob_get_clean();
//        }
//
//        if ($return === true) {
//            return $html;
//        } else {
//            echo '<pre>';
//            echo $html;
//            echo '</pre>';
//        }
//    }
//}
