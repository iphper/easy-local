<?php

namespace App\Controller;

class Index
{
    public function index()
    {
        return [
            'message' => 'Hello World!!!❤️'
        ];
    }

    public function example()
    {
        return view('example.html', [
            'name' => __METHOD__,
            'list' => ['Item 1', 'Item 2', 'Item 3', 'Item 4'],
            'b' => false
        ]);
    }
}
