<?php
namespace monitor;
use Vnet\redis\Redis;

class Subnet_get{
    public function get_subnet($keys){
        $network_add_config = config('config.network_add');
        $redis = new Redis($network_add_config);
        $result = $redis->keys();
        return $result;
    }
    public function parse($subnet_name){
        $network_add_config = config('config.network_add');
        $redis = new Redis($network_add_config);
        return $subnet_value = $redis->get($subnet_name);
    }
}
