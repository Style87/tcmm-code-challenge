<?php

namespace Phalcon\GetOpt;

trait WithMagicGetter
{
    public function __get($name)
    {
        $method = [$this, 'get' . ucfirst($name)];
        if (is_callable($method)) {
            return call_user_func($method);
        }
        return null;
    }
}
