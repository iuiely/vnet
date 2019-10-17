<?php
return [
    // global set
    'default' => [
        // request mode : web,api
        'mode'        => 'api',    
        'module'      => 'api',
        'app_path'    => realpath(dirname(dirname(__DIR__)).'/apps/'),
        'namespace'   => [
            'api'     => 'api',
        ],
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
        'router' =>[
            //default define route
            'module'          => 'api',    //默认模块
            'controller'      => 'index',    //默认控制器
            'action'          => 'index',     //默认操作
            'suffix'          =>  [
                'html'        => '.html',
                'php'         => '.php',    //url后缀
            ],
            //coustom define route
            'custom_route'    =>  [          //http custom路由
                //uri------------请求方法----模块/控制器/方法
                'networks'       => 'api/networks',
                'ports'          => 'api/ports',
            ],
        ]
    ],
];
