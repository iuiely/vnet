<?php
namespace Vnet\rmq;
use Vnet\Config;
class RabbitMqconnect{
    private static $_instance = null;
    protected $config=[
                  'host'=> '0.0.0.0',
                  'vhost'=> '/',
                  'port'=> '5672',
                  'login'=> '',
                  'password'=> '',
              ];
    private function __clone(){}
    private function __wakeup(){}
    private function __construct(array $config=[]){
        if (empty($config)) {
            $this->config = array_merge($this->config,Config::getInstance()->get('config.mq'));
        }elseif(!empty($config)){
            $this->config = array_merge($this->config,$config);
        }
    }
       
    public static function getInstance(array $config=[]){
        $class = __CLASS__;
        if (!(self::$_instance instanceof $class)){
            $rmq = new $class($config);
            self::$_instance = $rmq;
        }
        return self::$_instance;
    }

    public function NewMqconnect(){
        $mqconnection = new \AMQPConnection($this->config);
        if(!$mqconnection->connect()){
            die('Cannot connect to the broker');
        }else{
            return $mqchannel = new \AMQPChannel($mqconnection);
        }
    }
}
