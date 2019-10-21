<?php
namespace monitor;
use Vnet\rmq\RabbitMqexecute;
use Vnet\redis\Redis;
use monitor\Subnet_get;
use monitor\Vlan_Network;
use monitor\Subnet_stat;
 
class Console{
    public function execute(){
        go(function(){
            \Swoole\Timer::tick(60000,function(){
                $subnet_get = new Subnet_get();
                $subnet_stat = new Subnet_stat();
                $vlancheck = new Vlan_network();
                $rmq = new RabbitMqexecute();
                $queue = config('config.network.my_ip');
                $keys = $queue.'*';
                $subnet_list = $subnet_get->get_subnet($keys);
                foreach ($subnet_list as $subnet){
                    $sub_value = json_decode($subnet_get->parse($subnet),true);
                    $res = $vlancheck->check_subnet($sub_value);
var_dump($res);
                    if($res['result']===true){
                        $key = $res['subflag'];
                        $result = $subnet_stat->insert($key,'running',120);
                        $network_add_config = config('config.network_add');
                        $redis = new Redis($network_add_config);
                        $result = $redis->set($key,json_encode($sub_value),240);
                    }else{
                        $key = $res['subflag'];
                        $result = $subnet_stat->insert($key,'stop');
                        $exchange = 'vnet';
                        $route_key = $queue.'-server';
                        $type = '';
                        $flag = '';
                        $rmq->sendmsg(json_encode($sub_value), $exchange,$queue,$route_key);
                    }
                } 
            });
        });
    }
}
