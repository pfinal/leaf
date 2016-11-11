<?php

namespace Leaf;

class Request extends \Symfony\Component\HttpFoundation\Request
{
    public function all()
    {
        return $this->isMethod('GET') ? $this->query->all() : ($this->request->all() + $this->query->all());
    }
}