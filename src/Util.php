<?php

namespace Leaf;

/**
 * 工具类
 * @author  Zou Yiliang
 * @since   1.0
 */
class Util
{
    /**
     * 精度计算
     * 默认使用bcmath扩展
     *
     * var_dump(floor((0.1 + 0.7) * 10)); // 7
     * var_dump(floor(Util::calc(Util::calc(0.1, 0.7, '+'), 10, '*'))); // 8
     *
     * @param string $a
     * @param string $b
     * @param string $operator 操作符 支持: "+"、 "-"、 "*"、 "/"、 "comp"
     * @param int $scale 小数精度位数，默认2位
     * @return string|int 加减乖除运算，返回string。比较大小时，返回int(相等返回0, $a大于$b返回1, 否则返回-1)
     */
    public static function calc($a, $b, $operator, $scale = 2)
    {
        $scale = (int)$scale;
        $scale = $scale < 0 ? 0 : $scale;

        $bc = array(
            '+' => 'bcadd',
            '-' => 'bcsub',
            '*' => 'bcmul',
            '/' => 'bcdiv',
            'comp' => 'bccomp',
        );

        if (!array_key_exists($operator, $bc)) {
            throw new \Exception('operator invalid');
        }

        if (function_exists($bc[$operator])) {
            $fun = $bc[$operator];
            return $fun($a, $b, $scale);
        }

        switch ($operator) {
            case '+':
                $c = $a + $b;
                break;
            case '-':
                $c = $a - $b;
                break;
            case '*':
                $c = $a * $b;
                break;
            case '/':
                $c = $a / $b;
                break;
            case 'comp':

                // 按指定精度，去掉小数点，放大为整数字符串
                //$a = ltrim(number_format((float)$a, $scale, '', ''), '0');  //echo number_format(2.609, 2, '.', '');  => 2.61
                //$b = ltrim(number_format((float)$b, $scale, '', ''), '0');

                //$a = $a === '' ? '0' : $a;
                //$b = $b === '' ? '0' : $b;

                $a = self::numberCut($a, $scale);
                $b = self::numberCut($b, $scale);

                if ($a === $b) {
                    return 0;
                }

                return $a > $b ? 1 : -1;

            default:
                throw new \Exception('operator invalid');
        }

        // $c = number_format($c, $scale, '.', '');
        $c = self::numberCut($c, $scale);

        return $c;
    }

    /**
     * 保留指定精度的小数位，超出部份直接舍去
     * @param string $v
     * @param $scale
     * @return string
     */
    public static function numberCut($v, $scale)
    {
        $scale = (int)$scale;
        if ($scale < 0) {
            throw new \Exception('scale invalid');
        }

        $v = (string)$v;
        $dot = strpos($v, '.');

        //确保小数点后有足够位数的"0"
        $append = str_repeat('0', $scale);

        if ($dot === false) {          // "123" => "123.00"
            $dot = strlen($v);
            $v = $v . '.' . $append;
        } else {                       // "0.123" => "0.12300"
            $v = $v . $append;
        }

        if ($scale === 0) {
            return substr($v, 0, $dot);
        }

        return substr($v, 0, $dot + 1 + $scale);
    }

    /**
     * 获取相对时间
     * 规则：
     * 1分钟内    刚刚
     * 1小时内    XX分钟前
     * 1天内      XX小时前
     * 1-7天内    X天前
     * 7天以外    按格式化参数($format)
     * @param int $time
     * @param string $format
     * @return string
     */
    public static function relativeTime($time, $format = 'Y-m-d')
    {
        //各个时间段的unix时间戳
        $minute = 60;
        $hour = $minute * 60;
        $day = $hour * 24;
        $week = $day * 7;
        $currentTime = time();

        //两段Unix时间戳相差的时间戳
        $diff = $currentTime - $time;

        //计算
        if ($diff < $minute) {
            $res = '刚刚';
        } else if ($diff < $hour) {
            $res = floor($diff / $minute) . '分钟前';
        } else if ($diff < $day) {
            $res = floor($diff / $hour) . '小时前';
        } else if ($diff < $week) {
            $res = floor($diff / $day) . '天前';
        } else {
            $res = date($format, $time);
        }

        return $res;
    }

    /**
     * 清除非UTF8的特殊字符
     * @param string $str
     * @return string
     */
    public static function cleanUtf8Str($str)
    {
        if (self::isAscii($str) === false) {
            $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
        }
        return $str;
    }

    /**
     * 字符串截取 主要用于标题处理
     *
     * @param $string
     * @param int $length 截取长度(字节数,一个汉字两个字节长度,英文数字一个字节长)
     * @param bool $strip_tags 是否去掉标签
     * @param string $append 附加字符
     * @return string
     */
    public static function cutStringUtf8($string, $length, $strip_tags = true, $append = '...')
    {
        if ($strip_tags) {
            $string = strip_tags($string);
        }

        $arr = self::strSplitWithGbkLength($string, $length, $cut);

        return join('', $arr) . ($cut ? $append : '');
    }

    /**
     * 按GBK方式计算字符串长度(一个汉字长度为2，英文字符为1)
     * @param $str
     * @return int
     */
    public static function stringLengthGBK($str)
    {
        //Wrong charset, conversion from `UTF-8' to `GBK' is not allowed
        //return strlen(iconv('UTF-8', 'GBK', $str));

        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $str, $t_string);

        $len = 0;
        for ($i = 0; $i < count($t_string[0]); $i++) {
            $tmp = ord($t_string[0][$i]) < 128 ? 1 : 2;
            $len += $tmp;
        }

        return $len;
    }

    /**
     * 字符串分割到数组中
     * @param $string
     * @param int $maxLength 截取的最大长度(一个汉字用两个字节长度,英文数字一个字节长)  传0表示不截取
     * @param bool $cut 是否发生了截取
     * @return array
     */
    public static function strSplitWithGbkLength($string, $maxLength = 0, &$cut = false)
    {
        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
        preg_match_all($pa, $string, $t_string);

        if ($maxLength == 0) {
            return $t_string[0];
        }

        $len = 0;

        $res = array();
        for ($i = 0; $i < count($t_string[0]); $i++) {

            $tmp = ord($t_string[0][$i]) < 128 ? 1 : 2;

            if ($len + $tmp <= $maxLength) {
                $res[] = $t_string[0][$i];
                $len += $tmp;
            } else {
                $cut = true;
                return $res;
            }
        }

        return $res;
    }

//    /**
//     * 字符串截取(utf-8) 主要用于标题处理
//     *
//     * @param $string
//     * @param int $length 截取长度(字节数,一个汉字两个字节长度,英文数字一个字节长)
//     * @param bool $strip_tags 是否去掉标签
//     * @param string $append 附加字符
//     * @return string
//     */
//    public static function cutStringUtf8($string, $length, $strip_tags = true, $append = '...')
//    {
//        if ($strip_tags) {
//            $string = strip_tags($string);
//        }
//        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
//        preg_match_all($pa, $string, $t_string);
//        $str = "";
//        for ($i = 0; $i < count($t_string[0]); $i++) {
//            $str .= $t_string[0][$i];
//            if (strlen(@iconv('utf-8', 'gbk', $str)) >= $length) { // gbk一个汉字长度为2
//                if ($i != count($t_string[0]) - 1) $str .= $append;
//                break;
//            }
//        }
//        return $str;
//    }
//
//    /**
//     * 按GBK方式计算字符串长度(一个汉字长度为2，英文字符为1)
//     * @param $str
//     * @return int
//     */
//    public static function stringLengthGBK($str)
//    {
//        return strlen(@iconv('utf-8', 'gbk', $str));
//    }
//
//    /**
//     * 字符串分割到数组中
//     * @param $string
//     * @param int $length 字节长度(字节数,一个汉字用两个字节长度,英文数字一个字节长)
//     * @return array
//     */
//    public static function strSplitWithGbkLength($string, $length)
//    {
//        $arr = array();
//        $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
//        preg_match_all($pa, $string, $t_string);
//
//        $str = '';
//        for ($i = 0; $i < count($t_string[0]); $i++) {
//            if (self::stringLengthGBK($str . $t_string[0][$i]) <= $length) { // gbk一个汉字长度为2
//                $str .= $t_string[0][$i];
//            } else {
//                $arr[] = $str;
//                $str = $t_string[0][$i];
//            }
//        }
//
//        if (strlen($str) > 0) {
//            $arr[] = $str;
//        }
//
//        return $arr;
//    }

    /**
     * 是否Email
     * @param $str
     * @return bool
     */
    public static function isEmail($str)
    {
        $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
        return is_string($str) && strlen($str) <= 254 && preg_match($pattern, $str);
    }

    /**
     * 是否手机号码
     * @param $str
     * @return bool
     */
    public static function isMobile($str)
    {
        $pattern = '/^1\d{10}$/';
        return is_string($str) && strlen($str) == 11 && preg_match($pattern, $str);
    }

    /**
     * 是否ASCII字符
     * @param $str
     * @return bool
     */
    public static function isAscii($str)
    {
        return (preg_match('/[^\x00-\x7F]/S', $str) == 0);
    }

    /**
     * 检测是否在微信浏览器
     */
    public static function isWechatBrowser()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return strpos(strtolower($userAgent), 'micromessenger') !== false;
    }

    /**
     * 将字节转为易读的单位
     * @param $size
     * @param int $digits 保留的小数位数,默认为2位
     * @return string
     */
    public static function convertSize($size, $digits = 2)
    {
        if ($size <= 0) {
            return '0 KB';
        }
        $unit = array('', 'K', 'M', 'G', 'T', 'P');
        $base = 1024;
        $i = floor(log($size, $base));
        $n = count($unit);
        if ($i >= $n) {
            $i = $n - 1;
        }
        return round($size / pow($base, $i), $digits) . ' ' . $unit[$i] . 'B';
    }

    /**
     * 将字节转为易读的整数单位
     * @param $size
     * @return string
     */
    public static function convertSizeInt($size)
    {
        if ($size <= 0) {
            return '0 KB';
        }
        $units = array(3 => 'G', 2 => 'M', 1 => 'K', 0 => 'B');//单位字符,可类推添加更多字符.
        foreach ($units as $i => $unit) {
            if ($i > 0) {
                $n = $size / pow(1024, $i) % pow(1024, $i);
            } else {
                $n = $size % 1024;
            }

            $str = '';
            if ($n != 0) {
                $str .= " $n{$unit} ";
            }
        }
        return $str;
    }

    /**
     * 生成全局唯一标识符，类似 09315E33-480F-8635-E780-7A8E61FB49AA
     * @param null $namespace
     * @return string
     */
    public static function guid($namespace = null)
    {
        static $guid = '';
        $uid = uniqid(mt_rand(), true);

        $data = $namespace;
        $data .= isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();                 // 请求那一刻的时间戳
        $data .= isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : rand(0, 999999);  // 访问者操作系统信息
        $data .= isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : rand(0, 999999);          // 服务器IP
        $data .= isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : rand(0, 999999);          // 服务器端口号
        $data .= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : rand(0, 999999);          // 远程IP
        $data .= isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : rand(0, 999999);          // 远程端口

        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

        return $guid;
    }

    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        if ($recursive && !is_dir($parentDir)) {
            static::createDirectory($parentDir, $mode, true);
        }
        $result = mkdir($path, $mode);
        chmod($path, $mode);

        return $result;
    }

    public static function copyDirectory($src, $dst, $options = array())
    {
        if (!is_dir($dst)) {
            static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
        }

        $handle = opendir($src);
        if ($handle === false) {
            throw new \Exception('Unable to open directory: ' . $src);
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_file($from)) {
                copy($from, $to);
                if (isset($options['fileMode'])) {
                    @chmod($to, $options['fileMode']);
                }
            } else {
                static::copyDirectory($from, $to, $options);
            }

        }
        closedir($handle);
    }

    /**
     * 递归删除目录
     * @param string $dir 需要删除的目录
     */
    public static function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    self::removeDirectory("$dir/$file");
                }
            }
            rmdir($dir);
        } else if (file_exists($dir)) {
            unlink($dir);
        }
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int $length
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function random($length = 16)
    {
        if (function_exists('random_bytes')) {
            $string = '';

            while (($len = strlen($string)) < $length) {
                $size = $length - $len;

                $bytes = random_bytes($size);

                $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
            }

            return $string;
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);

            if ($bytes === false) {
                throw new \RuntimeException('Unable to generate random string.');
            }

            return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
        }

        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    /**
     * 与array_column功能类似，但此方法支持对象
     * @param array $input
     * @param $columnKey
     * @param null $indexKey
     * @return array
     */
    public static function arrayColumn(array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        foreach ($input as $key => $row) {
            $value = is_null($columnKey) ? $row : $row[$columnKey];
            if (is_null($indexKey)) {
                $result[] = $value;
            } else {
                $result[$row[$indexKey]] = $value;
            }
        }
        return $result;
    }

    /**
     * 解析注释块内容
     *
     * 调用示例
     * $ref = new \ReflectionClass('Test');
     * $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
     * $arr = \Rain\Util::parseDocCommentTags($methods[0]);
     * $parser = new \cebe\markdown\GithubMarkdown();
     * $parser->html5 = true;
     * $parser->parse('## ' . $arr['description']);
     *
     * @param \Reflector $reflection the comment block
     * @return array the parsed tags
     */
    public static function parseDocCommentTags($reflection)
    {
        $comment = $reflection->getDocComment();
        $comment = "@description \n" . strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($comment, '/'))), "\r", '');
        $parts = preg_split('/^\s*@/m', $comment, -1, PREG_SPLIT_NO_EMPTY);
        $tags = [];
        foreach ($parts as $part) {
            if (preg_match('/^(\w+)(.*)/ms', trim($part), $matches)) {
                $name = $matches[1];
                if (!isset($tags[$name])) {
                    $tags[$name] = trim($matches[2]);
                } elseif (is_array($tags[$name])) {
                    $tags[$name][] = trim($matches[2]);
                } else {
                    $tags[$name] = [$tags[$name], trim($matches[2])];
                }
            }
        }
        return $tags;
    }
}