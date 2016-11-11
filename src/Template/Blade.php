<?php

namespace Leaf\Template;

use Leaf\Application;
use Leaf\Util;

class Blade implements TemplateInterface
{
    protected $shareData = [];

    /**
     * 渲染视图
     *
     * @param string $view 模板文件
     * @param array $data
     * @return string
     */
    public function render($view, $data = array())
    {
        $app = Application::$app;

        if (file_exists($app['path'] . '/views')) {
            $path = [$app['path'] . '/views'];
        } else {
            $path = [$app['path']];
        }

        //View::render('@AcmeDemoBundle/welcome/index');
        if (stripos($view, '@') === 0) {
            $bundleName = substr($view, 1, stripos($view, '/') - 1);
            $bundle = Application::$app->getBundle($bundleName);

            //src/Acme/AcmeDemoBundle/views/welcome/index
            $path[] = $bundle->getPath() . '/resources/views/';

            //welcome/index
            $view = substr($view, strlen($bundleName) + 1);
        }

        // compiled file path
        $cachePath = rtrim(Application::$app->getRuntimePath(), '/\\') . DIRECTORY_SEPARATOR . 'blade';
        if (!file_exists($cachePath)) {
            Util::createDirectory($cachePath);
        }

        $compiler = new \PFinal\Blade\Compilers\BladeCompiler($cachePath);

        // you can add a custom directive if you want
        // in view file:  @datetime(1458364426)
        /*$compiler->directive('datetime', function ($timestamp) {
            return preg_replace('/(\(\d+\))/', '<?php echo date("Y-m-d H:i:s", $1); ?>', $timestamp);
        });*/

        $engine = new \PFinal\Blade\Engines\CompilerEngine($compiler);
        $finder = new \PFinal\Blade\FileViewFinder($path);

        // if your view file extension is not php or blade.php, use this to add it
        //$finder->addExtension('tpl');

        // get an instance of factory
        $factory = new \PFinal\Blade\Factory($engine, $finder);

        foreach ($this->shareData as $key => $value) {
            $factory->share($key, $value);
        }

        return $factory->make($view, $data)->render();
    }

    public function share($name, $value = null)
    {
        if (!is_array($name)) {
            $this->shareData[$name] = $value;
            return $this;
        }

        foreach ($name as $innerKey => $innerValue) {
            $this->shareData[$innerKey] = $innerValue;
        }
        return $this;
    }


}