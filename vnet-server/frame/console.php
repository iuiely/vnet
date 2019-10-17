<?php

require __DIR__.'/base.php';
class console{
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
            case 'status':
                self::status($config);
                break;
            case 'help':
                self::help();
                break;
            default:
                self::help();
        }
    }

    private static function start($config=[]){
        \Vnet\console\ConsoleServer::getInstance()->runAction($config);
    }
    protected static function stop($config=[]){
        $config = $config['server'];
        \Vnet\console\Daemon::getInstance($config)->stop();   
    }
    protected static function status($config=[]){
        $config = $config['server'];
        \Vnet\console\Daemon::getInstance($config)->status();   
    }
    protected static function help(){
        echo "Need input: start|stop|restart|status " . PHP_EOL;
    }
}

