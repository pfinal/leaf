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
        return new Response(static::encode($data), 200, array('Content-Type' => 'application/json; charset=UTF-8'));
    }

    /**
     * render json response with status `true`
     * @param $data
     * @param string $code
     * @return Response
     */
    public static function renderWithTrue($data = null, $code = '0')
    {
        return static::render(array('status' => true, 'data' => $data, 'code' => (string)$code));
    }

    /**
     * render json response with status `false`
     * @param string $data 错误消息
     * @param string $code 错误码
     * @return Response
     */
    public static function renderWithFalse($data = null, $code = '-1')
    {
        return static::render(array('status' => false, 'data' => $data, 'code' => (string)$code));
    }
}