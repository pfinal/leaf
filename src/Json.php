<?php

namespace Leaf;

use Symfony\Component\HttpFoundation\Response;

/**
 * render json
 * @author  Zou Yiliang
 * @since   1.0
 */
class Json
{
    protected static $cb = null;

    public static function setResultHandle($cb)
    {
        static::$cb = $cb;
    }


    /**
     * json encode
     * @param $data
     * @return string
     */
    public static function encode($data)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }

    /**
     * render json response
     * @param $data
     * @return Response
     */
    public static function render($data)
    {
        $str = static::encode($data);
        if ($str === false) {
            $err = '';
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    // No errors
                    break;
                case JSON_ERROR_DEPTH:
                    $err = ' Maximum stack depth exceeded.';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $err = ' Underflow or the modes mismatch.';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $err = ' Unexpected control character found.';
                    break;
                case JSON_ERROR_SYNTAX:
                    $err = ' Syntax error, malformed JSON.';
                    break;
                case JSON_ERROR_UTF8:
                    $err = ' Malformed UTF-8 characters, possibly incorrectly encoded.';
                    break;
                default:
                    $err = ' Unknown error.';
                    break;
            }

            throw new \RuntimeException("json encode error." . $err);
        }
        return new Response($str, 200, array('Content-Type' => 'application/json; charset=UTF-8'));
    }

    /**
     * render json response with status `true`
     * @param $data
     * @param string $code
     * @return Response
     */
    public static function renderWithTrue($data = null, $code = '0')
    {

        if (static::$cb) {
            return static::render(call_user_func_array(static::$cb, array(true, $data, (string)$code)));

        } else {
            return static::render(array('status' => true, 'data' => $data, 'code' => (string)$code));
        }

        return static::render($arr);
    }

    /**
     * render json response with status `false`
     * @param string $data 错误消息
     * @param string $code 错误码
     * @return Response
     */
    public static function renderWithFalse($data = null, $code = '-1')
    {
        if (static::$cb) {
            return static::render(call_user_func_array(static::$cb, array(false, $data, (string)$code)));
        } else {
            return static::render(array('status' => false, 'data' => $data, 'code' => (string)$code));
        }
    }
}
