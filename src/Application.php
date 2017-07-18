<?php

namespace Leaf;

use PFinal\Container\Container;

class Application extends Container
{
    /**
     * @var static
     */
    static $app;

    /**
     * @var array
     */
    protected $bundles = array();

    /**
     * Application constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        $config = array_replace_recursive([
            'id' => null,
            'path' => null,
            'timezone' => 'Asia/Chongqing',
            'charset' => 'UTF-8',
            'debug' => false,
            'env' => 'local',
            'params' => array(),
            'aliases' => array(
                'Leaf\Cache' => 'Leaf\Facade\CacheFacade',
                'Leaf\Mail' => 'Leaf\Facade\MailFacade',
                'Leaf\Log' => 'Leaf\Facade\LogFacade',
                'Leaf\Session' => 'Leaf\Facade\SessionFacade',
                'Leaf\Route' => 'Leaf\Facade\RouteFacade',
                'Leaf\DB' => 'Leaf\Facade\DBFacade',
                'Leaf\EM' => 'Leaf\Facade\EMFacade',
                'Leaf\Json' => 'Leaf\Facade\JsonFacade',
                'Leaf\Response' => 'Symfony\Component\HttpFoundation\Response',
                'Leaf\RedirectResponse' => 'Symfony\Component\HttpFoundation\RedirectResponse',
            ),
        ], $config);

        parent::__construct($config);

        static::$app = $this;

        $services = array(
            'Symfony\Component\HttpFoundation\Request' => function () {
                return static::$app['request'];
            },
            'Leaf\Request' => function () {
                return static::$app['request'];
            },
            'PFinal\Container\Container' => static::$app,
            'Leaf\Application' => static::$app,
        );

        foreach ($services as $name => $service) {
            $this[$name] = $service;
        }

        spl_autoload_register(function ($class) {
            if (array_key_exists($class, Application::$app['aliases'])) {
                class_alias(Application::$app['aliases'][$class], $class);
            }
        });
    }

    /**
     * 框架版本
     *
     * @return string
     */
    public static function getVersion()
    {
        return '2.4.0';
    }

    /**
     * 初始化
     */
    public function init()
    {
        if (empty($this['path'])) {
            throw new \Exception('App path is empty.');
        }
        $this['path'] = realpath($this['path']);

        if (is_null($this['id'])) {
            $this['id'] = md5($this['path']);
        }

        date_default_timezone_set($this['timezone']);

        self::errorHandler();
    }

    /**
     * Start
     *
     * @param null $request
     */
    public function run($request = null)
    {
        $this->init();

        $this['router'] = new \PFinal\Routing\Router($this);

        if ($request === null) {
            $this['request'] = \Leaf\Request::createFromGlobals();
        } else {
            $this['request'] = $request;
        }

        $this->route($this);

        $this['router']->dispatch($this['request'])->send();
    }

    /**
     * 加载路由
     *
     * @param $app
     */
    private function route($app)
    {
        //使用缓存
        $cacheFile = $this->getRuntimePath() . '/routes/_cache';
        $useCache = isset($app['router.cache']) ? $app['router.cache'] : false;
        if ($useCache && file_exists($cacheFile)) {
            $app['router']->setNodeData(unserialize(file_get_contents($cacheFile)));
            return;
        }

        //基础路由文件
        $routeFile = isset($app['router.file']) ? $app['router.file'] : $app['path'] . '/config/routes.php';

        if (file_exists($routeFile)) {
            require $routeFile;
        }

        //Bundle中的路由文件
        foreach ($this->bundles as $bundle) {
            if (file_exists($bundle->getPath() . '/resources/routes.php')) {
                require $bundle->getPath() . '/resources/routes.php';
            }
        }

        if ($useCache) {
            file_put_contents($cacheFile, serialize($app['router']->getNodeData()), LOCK_EX);
        }
    }

    /**
     * 错误处理
     */
    private function errorHandler()
    {
        //开启错误报告
        ini_set('display_errors', 'On');
        error_reporting(E_ALL);

        //错误处理
        $errorHandler = new ErrorHandler();
        set_error_handler(array($errorHandler, 'handleError'));
        set_exception_handler(array($errorHandler, 'handleException'));
    }

    /**
     * 注册Bundle
     *
     * @param Bundle $bundle
     */
    public function registerBundle(Bundle $bundle)
    {
        $name = $bundle->getName();
        if (isset($this->bundles[$name])) {
            throw new \LogicException(sprintf('Trying to register two bundles with the same name "%s".', $name));
        }
        $this->bundles[$name] = $bundle;
    }

    /**
     * 跟据Bundle名，获取Bundle对象
     *
     * @param $name
     * @return Bundle
     */
    public function getBundle($name)
    {
        if (!isset($this->bundles[$name])) {
            throw new \InvalidArgumentException(sprintf('Bundle "%s" does not exist or it is not enabled.', $name));
        }

        return $this->bundles[$name];
    }

    /**
     * 返回runtime目录，该目录需要写入权限
     *
     * @return string
     */
    public function getRuntimePath()
    {
        if (!$this->offsetExists('runtime.path')) {
            $this['runtime.path'] = $this['path'] . DIRECTORY_SEPARATOR . 'runtime';
        }

        if (!file_exists($this['runtime.path'])) {
            Util::createDirectory($this['runtime.path']);
        }

        return $this['runtime.path'];
    }

    /**
     * 返回当前环境 默认为"local"
     *
     * @return string
     */
    public function getEnv()
    {
        return $this['env'];
    }
}