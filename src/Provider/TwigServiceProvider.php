<?php

namespace Leaf\Provider;

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
        $app['twig.options'] = function ($app) {
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
            return new \Twig_Environment($app['twig.loader'], $app['twig.options']);
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
                return new \Leaf\Html();
            };
            $obj['debug'] = $app['twig.options']['debug'];

            return $obj;
        };

        $app['twig'] = function ($app) {

            /** @var $twig \Twig_Environment */
            $twig = $app['twig.environment_factory']($app);

            $twig->addFunction(new \Twig_SimpleFunction('url', function ($name, $params = [], $absoluteUrl = false) use ($app) {
                return \Leaf\Url::to($name, $params, $absoluteUrl);
            }));

            $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset = '', $absoluteUrl = false) use ($app) {
                return \Leaf\Url::asset($asset, $absoluteUrl);
            }));

            if (isset($app['debug']) && $app['debug']) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            $twig->addGlobal('app', $app['twig.app']);

            return $twig;
        };

    }
}

