<?php

namespace Ref247\Core;

class Loader
{
    private $actions = [];

    public function addAction($hook, $component, $callback)
    {
        $this->actions[] = [$hook, $component, $callback];
    }

    public function run()
    {
        foreach ($this->actions as $action) {
            add_action($action[0], [$action[1], $action[2]]);
        }
    }
}