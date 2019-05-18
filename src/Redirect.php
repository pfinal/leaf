<?php

namespace Leaf;

use Leaf\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Redirect
{
    /**
     * 重定向到指定路由或绝对地址
     * @param $url
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function to($url, $params = array())
    {
        // 以这些开头的直接跳转: "/"、"http://"、"https://"
        if (stripos($url, '/') === 0 || preg_match('/^http(s)?:\/\//i', $url)) {
            return new RedirectResponse($url);
        }

        $url = Url::to($url, $params);
        return new RedirectResponse($url);
    }

    /**
     * 返回根据前一个 URL 的重定向
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function back()
    {
        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :
            ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        return new RedirectResponse($url);
    }
}