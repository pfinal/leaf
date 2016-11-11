<?php

namespace Leaf\Template;

interface TemplateInterface
{
    /**
     * 渲染视图
     * @param string $view 模板文件
     * @param array $data
     * @return string
     */
    public function render($view, $data = array());

    public function share($name, $value = null);
}