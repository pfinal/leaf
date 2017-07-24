<?php

namespace Leaf\Auth;

interface Authenticatable
{
    /**
     * 返回用户ID
     * @return int
     */
    public function getId();
}