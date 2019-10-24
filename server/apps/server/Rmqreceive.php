<?php
namespace server;
use Vnet\HandleFilesystem;
use server\Network_set;
use server\Subnet_set;

class Rmqreceive{
    public function handle_data($data,$queue){
        date_default_timezone_set("Asia/Shanghai");
        $msg = $data->getBody();
        $json=json_decode($msg,true);
        $keys = array_keys($json);
        $flags= array_shift($keys);
        $action = $json[$flags];
        if($flags == 'network'){
            if($action == 'add'){
                $network = new Network_set();
                $result = $network->create($json);
                if($result ===1){
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Create Network ".$msg. " successful\n", FILE_APPEND);
                }else{
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Create Network ".$msg.' result '.$result." error\n", FILE_APPEND);
                }
            }elseif($action=='del'){
                $network = new Network_set();
                $result = $network->del($json);
                if($result===1){
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Delete Network ".$msg. " successful\n", FILE_APPEND);
                }else{
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Delete Network ".$msg.' result '.$result." error\n", FILE_APPEND);
                }
            }else{
                file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." The action ".$msg." error\n", FILE_APPEND);
            }
        }elseif($flags == 'subnet'){
            if($action == 'add'){
                $subnet=new Subnet_set();
                $result = $subnet->create($json);
                if($result ===1){
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Create subnet ".$msg. " successful\n", FILE_APPEND);
                }else{
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Create Subnet ".$msg.' result '.$result." failed\n", FILE_APPEND);
                }
            }elseif($action == 'del'){
                $subnet=new Subnet_set();
                $result = $subnet->del($json);
                if($result ===1){
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Delete subnet ".$msg. " successful\n", FILE_APPEND);
                }else{
                    file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." Delete Subnet ".$msg.' result '.$result." failed\n", FILE_APPEND);
                }
            }else{
                file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." The action ".$msg." error\n", FILE_APPEND);
            }
        }else{
            file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." The flags ".$msg." error\n", FILE_APPEND);
        }
        $queue->ack($data->getDeliveryTag());
    }
}
