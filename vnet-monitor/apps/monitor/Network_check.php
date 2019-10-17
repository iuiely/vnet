<?php
namespace monitor;
use Vnet\console\Dhcp_lib;
use Vnet\console\Ip_lib;
use Vnet\HandleFilesystem;

class Network_check{
    public function check_network($bridge='',$dhcpnamespace='',$routernamespace=''){
        $iplib=new Ip_lib();
        if(!empty($bridge)){
           $check_bridge = $iplib->get_device($bridge);
    var_dump($check_bridge);
        }
        if(!empty($dhcpnamespace)){
           $check_dhcp = $iplib->get_netns($dhcpnamespace);
    var_dump($check_dhcp);
        }
        if(!empty($routernamespace)){
           $check_router = $iplib->get_netns($routernamespace);
    var_dump($check_router);
        }
    }
}
