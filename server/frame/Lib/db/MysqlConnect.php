<?php
namespace Vnet\db;
class MysqlConnect {
/*       
    private static $_instance = null;
    protected static $config=[];
    private function __clone(){}
    private function __wakeup(){}
//    private function __construct(array $config=[]){
    public function __construct(array $config=[]){
        self::$config = $config;
    }
    public static function getInstance(array $config=[]){
        $class = __CLASS__;
        if (!(self::$_instance instanceof $class)){
            $mysql = new $class($config);
            self::$_instance = $mysql;
        }
        return self::$_instance;
    }
*/

    public static function Newconnect($config){
        $connection = new \Swoole\Coroutine\MySQL();
        $connection->connect($config);
        return $connection;
    }

    public function disconnect($connection){
        $connection->close();
    }

    public function isconnect($connection){
        return $connection->connected;
    }
}
