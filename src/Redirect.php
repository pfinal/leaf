<?php

namespace Leaf;

use Leaf\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Redirect
{
    /**
     * 重定向到指定路由
     * @param $url
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public static function to($url)
    {
        if (!preg_match('/^http(s)?:\/\//i', $url)) { //  'http://'    or  'https://'
            $url = Url::to($url);
        }

        return new RedirectResponse(Url::to($url));
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