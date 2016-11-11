<?php

namespace Leaf;

/**
 * 实现Object的数组方式访问
 * @author  Zou Yiliang
 */
trait ArrayAccessTrait
{
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        if (isset($this->$offset)) {
            return $this->$offset;
        }
        return $this->__get($offset);
    }

    public function offsetSet($offset, $item)
    {
        $this->$offset = $item;
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}
