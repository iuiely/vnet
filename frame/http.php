<?php

require __DIR__.'/base.php';

class http{
    private static $config = null;

    public static function run($config = []){
        $command = $config['command'];
        if(PHP_VERSION < '7.2'){
            die("PHP version must be 7.2 or greater .Current Version：".phpversion()."\n");
        }
        if(swoole_version() < '4.2.12'){
            die("Swoole version must be 4.2.12 pr greater .Current version : ".swoole_version()."\n");
        }
        if(php_sapi_name() != "cli"){
            die("This application must be execute in command line\n");
        }
        switch($command){
            case 'start':
                self::start($config);
                break;
            case 'stop':
                self::stop($config);
                break;
            case 'restart':
                self::restart($config);
                break;
            case 'kill':
                self::kill($config);
                break;
            case 'help':
                self::help($config);
                break;
            default:
                self::help($config);
        }
    }
    protected static function start($config = []){
        //$pid = posix_getpid();
        \Vnet\web\HttpServer::getInstance()->runAction($config);
    }
    protected static function stop($config = []){
        $pidfile = $config['api']['pid_path'].DIRECTORY_SEPARATOR.$config['api']['service'].'.pid';
        if($pidfile){
            $pid = explode(',',file_get_contents($pidfile));
            if($pid){
                var_dump($pid);
                posix_kill($pid['0'],SIGTERM);
            }
        }else{
            die("Service ".$config['api']['service']."not start");
        }
    }
    protected static function restart($config = []){
        $pidfile = $config['api']['pid_path'].DIRECTORY_SEPARATOR.$config['api']['service'].'.pid';
        if($pidfile){
            $pid = explode(',',file_get_contents($pidfile));
            if($pid){
                var_dump($pid);
                posix_kill($pid['0'], SIGUSR1);
                posix_kill($pid['1'], SIGUSR1);
            }
        }

    }
    protected static function kill($config = []){
        echo "kill";
    }
    protected static function help($config = []){
        echo "help".PHP_EOL;
    }
}
