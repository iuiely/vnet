<?php
namespace server;
use Vnet\rmq\RabbitMqexecute;
use Vnet\db\Mysql;
use server\Check_network;
use server\Network_set;
use server\Subnet_set;

class Console{
    public function execute(){
        go(function(){
            $mysql = new Mysql();
            $my_ip = config('config.network.my_ip');
            $sql = "select a.network_id,a.subnet_id,a.subnet_name,a.vlan_id,a.gateway,a.enable_dhcp,a.dhcp_range,";
            $sql.= "b.network_mode,b.brname,b.dhcpnamespace,b.routenamespace,b.stat,c.dhcp_msg ";
            $sql.= " from vnet_subnets a inner join vnet_networks b on a.network_id=b.network_id inner join vnet_dhcps c on a.subnet_id=c.subnet_id where c.dhcp_msg like '%$my_ip%'";
            $result = $mysql->select($sql);
            if(count($result['result'])){
                $checknetwork = new Check_network();
                foreach($result['result'] as $value){
                    $subnet['net_id']=$value['network_id'];
                    $subnet['brname']=$value['brname'];
                    $subnet['network_mode']=$value['network_mode'];
                    $subnet['dhcp_namespace']=$value['dhcpnamespace'];
                    $subnet['sub_id']=$value['subnet_id'];
                    $subnet['subname']=$value['subnet_name'];
                    $subnet['vlan_id']=$value['vlan_id'];
                    $subnet['gateway']=$value['gateway'];
                    $subnet['enable_dhcp']=$value['enable_dhcp'];
                    $subnet['dhcp_range']=$value['dhcp_range'];
                    $subnet['dhcp_info']=$value['dhcp_msg'];
                    $subnet['stat']=$value['stat'];
                    $subnet['routenamespace']=$value['routenamespace'];
                    $result = $checknetwork->network_check($subnet);
var_dump($result);
                    if($result['result']===false){
                        $network = new Network_set();
                        $result = $network->create($subnet);   
                    }
                    $result = $checknetwork->subnet_check($subnet);
var_dump($result);
                    if($result['result']===false){
                        $sub = new Subnet_set();
                        $result = $sub->create($subnet);
                    }
                }
            }
            $exchange = 'vnet';
            $queue = config('config.network.my_ip');
            $route_key = $queue.'-server';
            $type = '';
            $flag = '';
            $rmq = new RabbitMqexecute();
            $consumer = new Rmqreceive();
            $rmq->subscribe(array($consumer,'handle_data'),$exchange,$queue,$route_key,$type,$flag);
        });
    }
}
