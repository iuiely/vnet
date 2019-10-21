<?php
return [
    // global set
    'app_path'    => realpath(dirname(dirname(__DIR__)).'/apps/'),
    'namespace'   => [
        'api',
        'server',
        'monitor',
     ],
    'suffix'          =>  [
        'html'        => '.html',
        'php'         => '.php',    //url后缀
    ],
];
