<?php
namespace Vnet\rmq;
use Vnet\rmq\RabbitMqconnect;

class RabbitMqexecute{
    private static $instance = null;
    protected $chan;
    protected $exchange;
    protected $queue;
    protected $exchange_name='default_1';
    protected $queue_name = 'default_1';
    protected $route_key='';
    protected $type = 1;
    protected $durable=1;
    protected $config;
    
    private function __clone(){}
    private function __wakeup(){}
    public function __construct(array $config=null){
        if(!empty($config)){
            $this->newchannel($config);
        }else{
            $this->newchannel();
        }
    }

    public static function getInstance(array $config=[]){
        $class = __CLASS__;
        if (!(self::$instance instanceof $class)){
            $rmq = new $class($config);
            self::$instance = $rmq;
        }
        return self::$instance;
    }
       
    //  create exchange switches
    public function newchannel(array $config=null){
        if(!empty($config)){
            $this->chan = RabbitMqconnect::getInstance($config)->NewMqconnect();
        }else{
            $this->chan = RabbitMqconnect::getInstance()->NewMqconnect();
        }
    }
    //
    public function create_queue($exchange='',$queue='',$route_key='',$type='',$flag=''){
        // Set exchange switch name
        if($exchange != ''){
            $this->exchange_name = $exchange;
            $this->queue_name = $exchange;
        }
        // Set exchange switch type
        if($type != '') $thid->type = $type;
        // set exchange switch durable flag
        if($flag != '') $this->durable = $flag;
        // set exchange switch queue name
        if($queue != '') $this->queue_name = $queue;
        // set exchange switch route key
        if($route_key !='') $this->route_key = $route_key;
        // create exchange switch
        $this->exchange = new \AMQPExchange($this->chan);
        // TYPE EXPLAIN : 1=AMQP_EX_TYPE_DIRECT; 2=AMQP_EX_TYPE_FANOUT; 3=AMQP_EX_TYPE_TOPIC 4=AMQP_EX_TYPE_HEADERS
        if($this->type ==1){
            $this->exchange->setType(AMQP_EX_TYPE_DIRECT);
        }elseif($this->type ==2){
            $this->exchange->setType(AMQP_EX_TYPE_FANOUT);
        }elseif($this->type ==3){
            $this->exchange->setType(AMQP_EX_TYPE_TOPIC);
        }elseif($this->type ==4){
            $this->exchange->setType(AMQP_EX_TYPE_HEADERS);
        }else{
            die('Code error ,type must be the one of 1,2,3,4 ');
        }
        if($this->durable == 1){
            $this->exchange->setFlags(AMQP_DURABLE);
            $this->exchange->setFlags(AMQP_AUTODELETE);
        }
        $this->exchange->setName($this->exchange_name);
        $this->exchange->declareExchange();
        $this->queue = new \AMQPQueue($this->chan);
        if($this->durable == 1){
            $this->queue->setFlags(AMQP_DURABLE);
            $this->queue->setFlags(AMQP_AUTODELETE);
        }
        $this->queue->setName($this->queue_name);
        $this->queue->declareQueue();
        if($this->type ==1){
            $this->queue->bind($this->exchange_name, $this->route_key);
        }elseif($this->type ==3){
            $this->queue->bind($this->exchange_name, $this->route_key);
        }
    }
    public function sendmsg($msg,$exchange='',$queue='',$route_key='',$type='',$flag=''){
        // create exchange and queue,According to the route_key bound this two device
        $this->create_queue($exchange,$queue,$route_key,$type,$flag);
        // deal with message
        if(is_array($msg)){
            $msg = json_encode($msg);
        }else{
            $msg = trim(strval($msg));
        }
        //
        if($this->durable==1){
            $this->exchange->publish($msg,$this->route_key,AMQP_NOPARAM,array('delivery_mode' => 2));
        }else{
            $this->exchange->publish($msg,$this->route_key);
        }
    }
    public function subscribe($fun,$exchange='',$queue='',$route_key='',$type='',$flag='',$autoack=false){
        if(!$fun) return false;
        $this->create_queue($exchange,$queue,$route_key,$type,$flag);
        // subscribe message
        while(true){
            if($autoack){
                $this->queue->consume($fun,AMQP_AUTOACK);
            }else{
                $this->queue->consume($fun);
            }
        }
    }
    public function get($exchange='',$queue='',$route_key='',$type='',$flag='',$autoack=false){
        $this->create_queue($exchange,$queue,$route_key,$type,$flag);
        // subscribe message
        if($autoack){
            $msg=$this->queue->get(AMQP_AUTOACK);
        }else{
            $msg=$this->queue->get();
        }
        return ['msg'=>$msg,'queue'=>$this->queue];
    }
}

//$rmq = \Vnet\rmq\RabbitMqexecute::getInstance();
