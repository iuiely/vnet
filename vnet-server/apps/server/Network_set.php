<?php
namespace server;
use Vnet\console\Dhcp_lib;
use Vnet\console\Ip_lib;
use Vnet\HandleFilesystem;

class Network_set{
    public function create($data){
        $brname=$data['brname'];
        $dhcp_namespace = $data['dhcp_namespace'];
        $iplib=new IP_lib();
        if($brname){
            $create_vswitch = $iplib->create_vswitch($brname);
        }
        if($dhcp_namespace){
            $create_dhcp_namespace = $iplib->create_netns($dhcp_namespace);
        }
        if($create_vswitch[0]===0 && $create_dhcp_namespace[0]===0){
            return 1;
        }else{
            $iplib->remove_vswitch($brname);
            $iplib->remove_netns($dhcp_namespace);
            $result[] = $create_vswitch;
            $result[] = $create_dhcp_namespace;
            return json_encode($result);
        }
    }
    public function del($data){
        $mode = $data['network_mode'];
        $brname=$data['brname'];
        $dhcp_namespace = $data['dhcp_namespace'];
        $iplib=new IP_lib();
        if($mode =='vlan'){
            if(count($data)===5){
                if($brname){
                    $del_vswitch = $iplib->remove_vswitch($brname);
                }
                if($dhcp_namespace){
                    $del_namespace = $iplib->remove_netns($dhcp_namespace);
                }
                if($del_vswitch[0]===0 && $del_namespace[0]===0){
                    return 1;
                }else{
                    $result[]=$del_vswitch;
                    $result[]=$del_namespace;
                    return json_encode($data);
                }
            }else{
                $net_id=$data['net_id'];
                $map_device_interface = config('config.network.vlan_map_interface');
                $keys=array_keys($data);
                $dhcplib=new Dhcp_lib();
                for($i=6;$i<count($data);$i++){
                    $sub_id=$data[$keys[$i]]['sub_id'];
                    $vlanid=$data[$keys[$i]]['vlan_id'];
                    $vlanname=$map_device_interface.'.'.$vlanid;
                    $dhcp_pidfile=DHCP_FILE_PATH.'pid'.DIRECTORY_SEPARATOR.$net_id.DIRECTORY_SEPARATOR.$sub_id;
                    $remove_dhcp_server_res = $dhcplib->remove_dhcp_server($dhcp_pidfile);
                    $dhcp_hostsdir=DHCP_FILE_PATH.$net_id.DIRECTORY_SEPARATOR.$sub_id;
                    handlefile()->Delfile($dhcp_pidfile);
                    handlefile()->Deldir($dhcp_hostsdir);
                    $remove_vlan_res = $iplib->remove_nic($vlanname);
                }
                $hostsdir=DHCP_FILE_PATH.DIRECTORY_SEPARATOR.$net_id;
                $piddir=DHCP_FILE_PATH.DIRECTORY_SEPARATOR.'pid'.DIRECTORY_SEPARATOR.$net_id;
                handlefile()->Deldir($piddir);
                handlefile()->Deldir($hostsdir);
                $del_vswitch = $iplib->remove_vswitch($brname);
                $del_namespace = $iplib->remove_netns($dhcp_namespace);
                if($del_vswitch[0] ===0 && $del_namespace[0]===0){
                    return 1;
                }else{
                    $result[]=$del_vswitch;
                    $result[]=$del_namespace;
                    return json_encode($data);
                }
            }
        }
    }
}
