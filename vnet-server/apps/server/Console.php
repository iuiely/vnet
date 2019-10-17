<?php
namespace server;
use Vnet\rmq\RabbitMqexecute;

class Console{
    public function execute(){
        $exchange = 'vnet';
        $queue = config('config.network.my_ip');
        $route_key = $queue.'-server';
        $type = '';
        $flag = '';
        $rmq = new RabbitMqexecute();
        $consumer = new Rmqreceive();
        $rmq->subscribe(array($consumer,'handle_data'),$exchange,$queue,$route_key,$type,$flag);
    }
}
