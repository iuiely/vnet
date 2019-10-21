<?php
return [
    // global set
    'default' => [
        // 守护进程的应用命名空间名称
        'module'      => 'server',
        // 守护进程的主类名
        'class'       => 'console',
        // 应用的守护进程方法名
        'method'      => 'execute',
        // 应用的守护进程参数
        'parameter'   => '',
        'app_path'    => realpath(dirname(dirname(__DIR__)).'/apps/'),
        'namespace'   => [
            'server'  => 'server',
        ],
        'suffix'          =>  [
            'html'        => '.html',
            'php'         => '.php',    //url后缀
        ],
    ],
    'server' =>[
        'service'     => 'vnet-server',
        'pid_file'    => dirname(dirname(__DIR__)).'/runtime/pid/server.pid',
        'log_file'    => dirname(dirname(__DIR__)).'/logs/server/server.log',
    ]
];
