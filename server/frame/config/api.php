<?php
return [
    // global set
    'default' => [
        // 应用目录
        'module'      => 'api',
        'app_path'    => realpath(dirname(dirname(__DIR__)).'/apps/'),
        'suffix'          =>  [
            'html'        => '.html',
            'php'         => '.php',    //url后缀
        ],
    ],
    // api config  , http set
    'api' =>[
         //server config
        'ip'          => '0.0.0.0',
        'port'        => '4203',
        'type'        => 'http',
        'service'     => 'vnet-api',
        'pid_path'    => '/var/run',
        'gzip'        => 0,
        //swoole config
        'set'         => [
            'daemonize'                => 0,
            'enable_static_handler'    => true,
            'document_root'            => realpath(dirname(dirname(__DIR__)).'/public/'),
            'worker_num'               => 4,
            'max_request'              => 10000,
            'dispatch_mode'            => 7,
            'reload_async'             => true,
            'max_wait_time'            => 600,
            'max_coroutine'            => 3000,
            'buffer_output_size'       => 4 * 1024 * 1024,
            'task_enable_coroutine'    => true,
            'enable_reuse_port'        => true,
            'open_tcp_nodelay'         => true,
            'log_file'                 => dirname(dirname(__DIR__)).'/logs/api/api.log'
        ],
    ],
    'route' =>[
        'networks' => array(
            ['get','networks','networks/view',''],
            ['get','networks/id','networks/show','id'],
            ['post','networks','networks/add',''],
            ['put','networks/id','networks/update','id'],
            ['delete','networks/id','networks/del','id'],
        ),
        'nodes' => array(
            ['get','nodes','nodes/view',''],
            ['get','nodes/id','nodes/show','id'],
            ['post','nodes','nodes/add',''],
            ['put','nodes/id','nodes/update','id'],
            ['delete','nodes/id','nodes/del','id'],
        ),
        'subnets' => array(
            ['get','subnets','subnets/view',''],
            ['get','subnets/id','subnets/show','id'],
            ['post','subnets','subnets/add',''],
            ['put','subnets/id','subnets/update','id'],
            ['delete','subnets/id','subnets/del','id'],
        ),
        'ports' => array(
            ['get','ports','ports/view',''],
            ['get','ports/id','ports/show','id'],
            ['post','ports','ports/dhcp',''],
            ['put','ports/id','ports/update','id'],
            ['delete','ports/id','ports/del','id'],
        ),
        'fix' => array(
            ['post','fix','fix/create',''],
            ['put','fix/id','fix/update','id'],
            ['delete','fix/id','fix/del','id'],
        ),
    ],
];
