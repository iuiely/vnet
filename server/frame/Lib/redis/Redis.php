<?php
namespace Vnet\redis;
use Vnet\redis\RedisConnect;

class Redis{
    protected $connection;
    protected $config =[];
    public function __construct(array $config){
        if(empty($config)){
            throw new \RuntimeException(printf("Error!Redis config is empty"));
        }
        $this->config = $config;
        $this->connect($this->config);
    }
    public function connect(array $config){
        $this->connection = RedisConnect::connect($config);
        return $this->connection;
    }
    public function keys($key=null){
        if(empty($key)){
            return $this->connection->keys('*');
        }elseif(!empty($key)){
            return $this->connection->keys($key);
        }
    }
    public function get($key){
        return $this->connection->get($key);
    }
    public function set($key,$value,$expire=''){
        if(empty($expire)){
            $result = $this->connection->set($key,$value);
            return $result;
        }else{
            $result = $this->connection->set($key,$value,$expire);
            return $result;
        }
    }
    public function del($key){
        if(empty($key)){
            return false;
        }
        return $this->connection->del($key);
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
    $redis = new Redis($config);
    $result2 = $redis->set('bbb','bbb1');
    $result1 = $redis->get('bbb');
    var_dump($result1,$result2);

});
*/
