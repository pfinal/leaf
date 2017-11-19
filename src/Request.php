<?php

namespace Leaf;

class Request extends \Symfony\Component\HttpFoundation\Request
{
    /**
     * @var \Closure
     */
    protected $routeResolver;

    public function all()
    {
        return $this->isMethod('GET') ? $this->query->all() : ($this->request->all() + $this->query->all());
    }

    /**
     * @return \Closure
     */
    public function getRouteResolver()
    {
        return $this->routeResolver ? $this->routeResolver : function () {
            //nothing to do
        };
    }

    /**
     * @param  \Closure $callback
     * @return $this
     */
    public function setRouteResolver(\Closure $callback)
    {
        $this->routeResolver = $callback;

        return $this;
    }

    /**
     * Get the route handling the request.
     * @return array|null
     */
    public function route()
    {
        return call_user_func($this->getRouteResolver());
    }
}