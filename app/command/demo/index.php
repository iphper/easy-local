<?php

namespace App\Command\Demo;

class Index
{
    /**
     * @command ./index demo/index/index
     */
    public function index()
    {
        return 'hello world';
    }
}
