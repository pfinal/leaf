<?php

namespace Leaf;

use Leaf\Template\Blade;
use Leaf\Template\Twig;

/**
 * 视图操作帮助类
 *
 * @author  Zou Yiliang
 * @since   1.0
 */
class View
{
    protected static $shareData = array();

    /**
     *
     * 渲染视图
     *
     * @param string $view 模板文件
     * @param array $data
     * @return string
     */
    public static function render($view, $data = array())
    {
        if (substr($view, -5) === '.twig') {
            $drive = new Twig();
        } else {
            $drive = new Blade();
        }

        $drive->share(static::$shareData);
        return $drive->render($view, $data);
    }

    /**
     * 把数据共享给所有模板文件
     *
     * @param $name
     * @param $value
     */
    public static function share($name, $value)
    {
        if (!is_array($name)) {
            static::$shareData[$name] = $value;
            return;
        }

        foreach ($name as $innerKey => $innerValue) {
            static::$shareData[$innerKey] = $innerValue;
        }
    }

    /**
     * 渲染文本模板
     *
     * echo View::renderText('hello {{ user }}',['user'=>'jack']);
     *
     * @param string $template 模板字符串
     * @param array $data
     * @return string
     */
    public static function renderText($template, $data = array())
    {
        $twig = Application::$app['twig'];
        $twig->setCache(false);
        $tpl = $twig->createTemplate($template);
        return $tpl->render($data);
    }
}