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

    public function only($keys)
    {
        $keys = (array)$keys;
        return array_filter($this->all(), function ($k) use ($keys) {
            return in_array($k, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Content-Type: application/json
     */
    public function input()
    {
        if (strcasecmp($this->headers->get('content-type'), 'application/json') != 0) {
            return [];
        }
        $content = file_get_contents('php://input');
        return json_decode($content, true);
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