<?php

namespace App\Controllers;

class TaskRegistry extends BaseController
{
    public function index()
    {
        return view('pages/task_registry/index', [
            'title' => 'API Task Registry | MD-Bridge',
            'page'  => 'tasks',
        ]);
    }
}
