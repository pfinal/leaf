<?php

namespace Leaf\Template;

use Leaf\Application;

/**
 * Twig模板
 *
 * @author  Zou Yiliang
 * @since   1.0
 */
class Twig implements TemplateInterface
{
    /**
     *
     * 渲染视图
     *
     * @param string $view 模板文件
     * @param array $data
     * @return string
     */
    public function render($view, $data = array())
    {
        // View::render('@AcmeDemoBundle/welcome/index.twig');
        if (stripos($view, '@') === 0) {

            $app = Application::$app;

            $bundleName = substr($view, 1, stripos($view, '/') - 1);
            $bundle = $app->getBundle($bundleName);

            // views/AcmeDemoBundle/welcome/index.twig
            if (file_exists($app['twig.path'] . '/' . $bundleName)) {
                $app['twig.loader.filesystem'] = $app->extend('twig.loader.filesystem', function ($filesystem, $app) use ($bundle) {
                    /** @var $filesystem \Twig_Loader_Filesystem */
                    $filesystem->addPath($app['twig.path'] . '/' . $bundle->getName(), $bundle->getName());
                    return $filesystem;
                });
            }
            
            // src/Acme/AcmeDemoBundle/views/welcome/index.twig
            $app['twig.loader.filesystem'] = $app->extend('twig.loader.filesystem', function ($filesystem, $app) use ($bundle) {
                /** @var $filesystem \Twig_Loader_Filesystem */
                $filesystem->addPath($bundle->getPath() . '/resources/views/', $bundle->getName());
                return $filesystem;
            });

        }

        return Application::$app['twig']->render($view, $data);
    }

    /**
     * 把数据共享给所有模板文件
     *
     * @param string|array $name
     * @param $value
     * @return static
     */
    public function share($name, $value = null)
    {
        if (!is_array($name)) {
            Application::$app['twig'] = Application::$app->extend('twig', function ($twig, $app) use ($name, $value) {
                $twig->addGlobal($name, is_callable($value) ? call_user_func($value) : $value);
                return $twig;
            });
            return $this;
        }

        foreach ($name as $innerKey => $innerValue) {
            Application::$app['twig'] = Application::$app->extend('twig', function ($twig, $app) use ($innerKey, $innerValue) {
                $twig->addGlobal($innerKey, is_callable($innerValue) ? call_user_func($innerValue) : $innerValue);
                return $twig;
            });

        }
        return $this;
    }

}