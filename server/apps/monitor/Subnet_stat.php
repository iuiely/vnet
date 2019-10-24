<?php
namespace monitor;
use Vnet\redis\Redis;

class Subnet_stat{
    public function insert($subnet,$value,$expire=''){
        if(!empty($subnet)&&!empty($value)&&!empty($expire)){
            $network_run_config = config('config.network_run');
            $redis = new Redis($network_run_config);
            $result = $redis->set($subnet,$value,$expire);
            return $result;
        }elseif(!empty($subnet)&&!empty($value)){
            $network_run_config = config('config.network_run');
            $redis = new Redis($network_run_config);
            $result = $redis->set($subnet,$value);
            return $result;
        }else{
            return false;
        }
    }
    public function parse($subnet_name){
        $network_add_config = config('config.network_add');
        $redis = new Redis($network_add_config);
        return $subnet_value = $redis->get($subnet_name);
    } 
}
