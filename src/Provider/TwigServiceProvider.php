<?php

namespace Leaf\Provider;

use Leaf\Html;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Leaf\Application;

/**
 *
 * 如果需要扩展twig，不要在app.php中，直接调用$twig->addGlobal，请用下面这种方式:
 * $app['twig'] = $app->extend('twig', function ($twig, $app) {
 *      $twig->addGlobal('request', $app->getRequest());
 *      $twig->addFunction(new \Twig_SimpleFunction('getApiUrl', function ($route, array $params = []) use ($app) {
 *          return \ShopBundle\Service\ShopService::getApiUrl($route, $params);
 *      }));
 *      return $twig;
 * });
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['twig.config'] = function ($app) {
            return array(
                'charset' => isset($app['charset']) ? $app['charset'] : 'UTF-8',
                'debug' => isset($app['debug']) ? $app['debug'] : false,
                'strict_variables' => isset($app['debug']) ? $app['debug'] : false,
                'cache' => rtrim(Application::$app->getRuntimePath(), '/\\') . DIRECTORY_SEPARATOR . 'twig',
                'auto_reload' => true,
            );
        };

        $app['twig.templates'] = function ($app) {
            return array();
        };

        $app['twig.loader.array'] = function ($app) {
            return new \Twig_Loader_Array($app['twig.templates']);
        };

        $app['twig.path'] = function ($app) {
            if (file_exists($app['path'] . '/views')) {
                return $app['path'] . '/views';
            }
            return $app['path'];
        };

        $app['twig.loader.filesystem'] = function ($app) {
            return new \Twig_Loader_Filesystem($app['twig.path']);
        };

        $app['twig.loader'] = function ($app) {
            return new \Twig_Loader_Chain(array(
                $app['twig.loader.array'],
                $app['twig.loader.filesystem'],
            ));
        };


        $app['twig.environment_factory'] = $app->protect(function ($app) {
            return new \Twig_Environment($app['twig.loader'], $app['twig.config']);
        });

        $app['twig.app'] = function (Application $app) {

            $obj = new Container();

            $obj['request'] = function () use ($app) {
                return $app['request'];
            };
            $obj['session'] = function () use ($app) {
                return $app['session'];
            };
            $obj['html'] = function () {
                return new TwigHtml();
            };
            $obj['debug'] = $app['twig.config']['debug'];

            return $obj;
        };

        $app['twig'] = function ($app) {

            /** @var $twig \Twig_Environment */
            $twig = $app['twig.environment_factory']($app);

            $twig->addFunction(new \Twig_SimpleFunction('url', function ($name, $params = array(), $absoluteUrl = false) use ($app) {
                //使用Twig_Markup对象，不做html实体转义
                //否则 {{url('test',{a:1,b:2})}} 会被转义为 test?a=1&amp;b=2
                return new \Twig_Markup(\Leaf\Url::to($name, $params, $absoluteUrl), 'utf-8');
            }));

            $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset = '', $absoluteUrl = false) use ($app) {
                return new \Twig_Markup(\Leaf\Url::asset($asset, $absoluteUrl), 'utf-8');
            }));

            //生成排序超链接
            //{{ sort_by('username', '用户名') }}
            $twig->addFunction(new \Twig_SimpleFunction('sort_by', function ($sort = '', $text = '') use ($app) {
                $url = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                $current = Application::$app['request']->get('sortby');

                if (!empty($current)) {

                    if ($sort === ltrim($current, '-')) {

                        if ($current[0] === '-') {
                            $text = $text . ' <span class="glyphicon glyphicon-sort-by-attributes-alt"></span>';
                        } else {
                            $sort = "-$sort";
                            $text = $text . ' <span class="glyphicon glyphicon-sort-by-attributes"></span>';
                        }
                    }
                    $url = preg_replace('/([\?&]sortby=)[\-]?[\w\.]+/', "$1$sort", $url);
                } else {
                    $url = $url . ((strpos($url, '?') === false) ? '?' : '&') . 'sortby=' . $sort;
                }
                return new \Twig_Markup(Html::link($text, $url), 'utf-8');
            }));

            if (isset($app['debug']) && $app['debug']) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            $twig->addGlobal('app', $app['twig.app']);

            return $twig;
        };
    }
}

