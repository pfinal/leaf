<?php

namespace Leaf\Provider;

class TwigHtml
{
    public function __call($name, $arguments)
    {
        $html = call_user_func_array(['Leaf\Html', $name], $arguments);
        return new \Twig_Markup($html, 'utf-8');
    }
}