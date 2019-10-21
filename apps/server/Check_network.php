<?php
namespace server;

class Check_network{
    public function network_check($network){
       $result=[];
       $my_ip = config('config.network.my_ip');
       $bridge = $network['brname'];
       $dhcpnamespace  = $network['dhcp_namespace'];
       if($this->check_vswitch($bridge)&&$this->check_dhcp_namespace($dhcpnamespace)){
           $result['result'] =true;
           return $result;
       }else{
           $result['result'] =false;
           $result['network'] = false;
           return $result;
       }
    }
    public function subnet_check($subnet){
       $result=[];
       $my_ip = config('config.network.my_ip');
       $dhcpnamespace  = $subnet['dhcp_namespace'];
       $dhcpinfo = json_decode($subnet['dhcp_info'],true);
       $dhcp_host = $dhcpinfo['my_ip'];
       if($my_ip != $dhcp_host){
           $result['result'] =false;
           $result['dhcp_host'] = false; 
           return $result; 
       }
       $map_device_interface = config('config.network.vlan_map_interface');
       $vlanid = $subnet['vlan_id'];
       $vlanname=$map_device_interface.'.'.$vlanid;
       if(!$this->check_vlan_nic($vlanname)){
           $result['result'] =false;
           $result['vlanif'] = false;
           return $result;
       }
       $dhcp_nic = $dhcpinfo['out_if'];
       $tap_nic = $dhcpinfo['in_if'];
       if(!$this->check_dhcp_nic($dhcp_nic) ||!$this->check_tap_nic($tap_nic,$dhcpnamespace)){
           $result['result'] =false;
           $result['veth'] = false;
           return $result;
       }
       $subid = $subnet['sub_id'];
       $netid = $subnet['net_id'];
       $dhcp_hostsfile = DHCP_FILE_PATH.$netid.DIRECTORY_SEPARATOR.$subid.DIRECTORY_SEPARATOR.$subid;
       if(!$this->check_dhcp_hostfile($dhcp_hostsfile)){
           $result['result'] =false;
           $result['hostfile'] = false;
           return $result;
       }
       $dhcp_pidfile=DHCP_FILE_PATH.'pid'.DIRECTORY_SEPARATOR.$netid.DIRECTORY_SEPARATOR.$subid;
       if(!$this->check_dhcp_pid($dhcp_dhcp_pidfile)){
           $result['result'] =false;
           $result['pidfile'] = false;
           return $result;
       }
       $result['result'] =true;
       return $result;
    }
    public function check_vswitch($bridge){
        if(!empty($bridge)){
            $check_bridge = iplib()->is_vswitch($bridge);
            if($check_bridge[0]){
                return true;
            }else{
                return false;
            }
        }
    }
    public function check_dhcp_namespace($dhcpnamespace){
        if(!empty($dhcpnamespace)){
            $check_dhcp = iplib()->get_netns($dhcpnamespace);
            if($check_dhcp[0]){
                return true;
            }else{
                return false;
            }
        }
    }
    public function check_router_namespace(){
        if(!empty($routernamespace)){
            $check_router = iplib()->get_netns($routernamespace);
        }
    }
    public function check_vlan_nic($ifname){
        if(!empty($ifname)){
            $check_vlan = iplib()->get_device($ifname);
            if($check_vlan[0]){
                return true;
            }else{
                return false;
            }
        }
    }
    public function check_dhcp_nic($ifname){
        if(!empty($ifname)){
            $check_dhcp = iplib()->get_device($ifname);
            if($check_dhcp[0]){
                return true;
            }else{
                return false;
            }
        }
    }
    public function check_tap_nic($ifname,$dhcpnamespace){
        if(!empty($ifname)){
            $check_tap = iplib()->get_device($ifname,$dhcpnamespace);
            if($check_tap[0]){
                return true;
            }else{
                return false;
            }
        }
    }
    public function check_dhcp_hostfile($file){
        if(!empty($file)){
            $check_hostfile = file_exists($file);
            if($check_hostfile){
                return true;
            }else{
                return false;
            }
        }
    }
    public function check_dhcp_pid($pid){
        if(!empty($pid)){
            $check_pid = file_exists('/proc/'.$pid);
            if($check_pid){
                return true;
            }else{
                return false;
            }
        }
    }
}
