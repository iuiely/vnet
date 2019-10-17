<?php
namespace Vnet\db;
class MysqlConnect {
    private static $_instance = null;
    protected $config=[];
    private function __clone(){}
    private function __wakeup(){}
    private function __construct(array $config=[]){
        $this->config = $config;
    }
       
    public static function getInstance(array $config=[]){
        $class = __CLASS__;
        if (!(self::$_instance instanceof $class)){
            $mysql = new $class($config);
            self::$_instance = $mysql;
        }
        return self::$_instance;
    }

    public function Newconnect(){
        $connection = new \Swoole\Coroutine\MySQL();
        $connection->connect($this->config);
        return $connection;
    }

    public function disconnect($connection){
        $connection->close();
    }

    public function isconnect($connection){
        return $connection->connected;
    }
}
