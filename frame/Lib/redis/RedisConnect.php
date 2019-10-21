<?php
namespace Vnet\redis;
use Swoole\Coroutine\Redis;

class RedisConnect{
    public static function connect(array $config){
        $connection = new Redis($config['options']??[]);
        $result = $connection->connect($config['host'],$config['port']);
        if($result ===false){
            throw new \RuntimeException(printf("Connect to redis server [%s] %s",$connection->errCode,$connection->errMsg));
        }
        if(isset($config['password'])){
            $password = (string)$config['password'];
            $connection->auth($password);
        }
        if(isset($config['database'])){
            $database = $config['database'];
            $connection->select($database);
        }
        return $connection;
    }
    public function disconnect($connection){
        $connection->close();
    }
    public function isconnect($connection){
        return $connection->connected;
    }
}
/*
go(function(){
    $config = 
            ['host'    => '10.200.2.162',
            'port'     => '6379',
            'database' => 0,
            'password' => null,
            'options'  => [
                'connect_timeout' => 1,
                'timeout'         => 5,
            ]];
    $redis = RedisConnect::connect($config);
//    $result2 = $redis->set('bbb','bbb1');
//    $result1 = $redis->get('aaa');
    var_dump($redis);

});
*/
