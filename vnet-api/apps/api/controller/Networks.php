<?php
namespace api\controller;
use Vnet\db\Mysql;
use Vnet\rmq\RabbitMqexecute;
use Vnet\db\Subinfo;

class Networks {
    public function view($user='admin',$draw=0,$start=0,$length=15,$cluster="",$findnetwork=""){
        //GET
        $method = strtolower(request()->get_method());
        if($method == 'get'){
            $request = request()->get();
        }
        if(!empty($request)){
            $draw = $request['draw'];
            $start = $request['start'];
            $length = $request['length'];
            $user = $request['user'];
            $cluster = $request['cluster'];
            $findnetwork = $request['findnetwork'];
        }else{
            $draw = $draw;
            $start = $start;
            $length = $length;
            $user = $user;
            $cluster = $cluster;
            $findnetwork = $findnetwork;
        }
        if($findnetwork === "" && $user =="admin" && $cluster===""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($findnetwork === "" && $user =="admin" && $cluster !=""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks where clusters='$cluster'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks where clusters='$cluster' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($findnetwork != "" && $user =="admin" && $cluster===""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks where network_name like '%$findnetwork%'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks where network_name like '%$findnetwork%' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($findnetwork != "" && $user =="admin" && $cluster!=""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks where clusters='$cluster' and network_name like '%$findnetwork%'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks where clusters='$cluster' and network_name like '%$findnetwork%' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);            
        }elseif($findnetwork === "" && $user !="admin" && $cluster===""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks where users='$user'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks where users='$user' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($findnetwork === "" && $user !="admin" && $cluster!=""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks where users='$user' and clusters='$cluster'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks where users='$user' and clusters='$cluster' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($findnetwork != "" && $user !="admin" && $cluster===""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks where users='$user' and network_name like '%$findnetwork%'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks where users='$user' and network_name like '%$findnetwork%' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($findnetwork != "" && $user !="admin" && $cluster!=""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_networks where users='$user' and clusters='$cluster' and network_name like '%$findnetwork%'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_networks where users='$user' and clusters='$cluster' and network_name like '%$findnetwork%' limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }
    }
    public function show($id){
        //GET
        $method = strtolower(request()->get_method());
        if($method == 'get'){
            $request = request()->get();
        }
        $id = $id[0];
        $sql = "select b.id,a.network_name,b.subnet_name,b.subnet_id from vnet_networks as a inner join vnet_subnets as b on a.network_id=b.network_id where a.id='$id'";
        $mysql = new Mysql();
        $result = $mysql->query($sql);
        return json_encode($result['result']);
    }
    public function add(){
        //POST
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method == 'post'){
            if($header['content-type'] =='application/json'){
                $request = json_decode(request()->get_rawbody(),true);
            }
        }
        date_default_timezone_set("Asia/Shanghai");
        $now_time = date("Y-m-d H:i:s");
        $netid =  'n'.substr(hash("sha256",sha1(md5(uniqid(microtime(true),true)))),0,19);
        $cluster = $request['cluster'];
        $name = $request['name'];
        $user = $request['user'];
        $group = $request['group'];
        $mode = $request['mode'];
        $swname = 'br-'.substr(hash("sha256",sha1(md5(uniqid(microtime(true),true)))),0,10);
        $vlan_model = config('config.network.enable_vlan');
        $vxlan_model = config('config.network.enable_vxlan');
        if(empty($netid)||empty($cluster)||empty($name)||empty($user)||empty($group)||empty($mode)||empty($swname)){
            return json_encode('{"status":"402","error""Parameter error"}');
        }
        if($mode =='vlan' && $vlan_model){
            // 从配置文件中取得VLAN的map网卡,取得VLAN的drive模式
            $vlan_drive = config('config.network.vlan_drive');
            // 确认这个网络的名称和网络ID不重复
            $sql = "select count(id) as row from vnet_networks where network_name='$name' and clusters='$cluster' or network_id='$netid'";
            $mysql = new Mysql();
            $res = $mysql->query($sql);
            $count = $res['result']['0']['row'];
            if($count !== '0'){
                $data['status'] = 402;
                $data['sql'] =  $sql;
                $data['sqlresult'] = $res;
                $data['error'] = 'The Network exists';
                return json_encode($data);
            }
            // 生成网络namespace
            $dhcpnamespacename = "vdhcp-".$netid;
            $routenamespacename = "router-".$netid;
            $getnodesql = "select nodes,dhcp,router from vnet_nodes where uses='server'";
            $noderes = $mysql->query($getnodesql);
            $dhcpstatus=[];
            $routerstatus=[];
            foreach($noderes['result'] as $value){
                $dhcpstatus[] = $value['dhcp'];
                $routerstatus[]=$value['router'];
            }
            if(count(array_unique($dhcpstatus))!==1 && count(array_unique($routerstatus))!==1){
                return json_encode('{"status":"402","data":"Resource types are inconsistent "}'); // Configuration files are inconsistent
            }
            if(array_unique($routerstatus)[0]=='1' && array_unique($dhcpstatus)[0]=='1'){
                $rmq_msg = ['network'=>'add','brname'=>$swname,'dhcp_namespace'=>$dhcpnamespacename,'router_namespace'=>$routenamespacename];
                unset($netsql); 
                $netsql = "insert into vnet_networks(network_id,network_name,clusters,users,groups,network_mode,brname,dhcpnamespace,routenamespace)";
                $netsql .= "value('$netid','$name','$cluster','$user','$group','$mode','$swname','$dhcpnamespacename','$routenamespacename')";
            }elseif(array_unique($routerstatus)[0]=='1' && array_unique($dhcpstatus)[0]=='0'){
                unset($netsql); 
                $rmq_msg = ['network'=>'add','brname'=>$swname,'dhcp_namespace'=>'','router_namespace'=>$routenamespacename];
                $netsql = "insert into vnet_networks(network_id,network_name,clusters,users,groups,network_mode,brname,dhcpnamespace,routenamespace)";
                $netsql .= "value('$netid','$name','$cluster','$user','$group','$mode','$swname','','$routenamespacename')";
            }elseif(array_unique($routerstatus)[0]=='0' && array_unique($dhcpstatus)[0]=='1'){
                unset($netsql); 
                $rmq_msg = ['network'=>'add','brname'=>$swname,'dhcp_namespace'=>$dhcpnamespacename,'router_namespace'=>''];
                $netsql = "insert into vnet_networks(network_id,network_name,clusters,users,groups,network_mode,brname,dhcpnamespace,routenamespace)";
                $netsql .= "value('$netid','$name','$cluster','$user','$group','$mode','$swname','$dhcpnamespacename','')";
            }else{
                unset($netsql); 
                $rmq_msg = ['network'=>'add','brname'=>$swname,'dhcp_namespace'=>'','router_namespace'=>''];
                $netsql = "insert into vnet_networks(network_id,network_name,clusters,users,groups,network_mode,brname,dhcpnamespace,routenamespace)";
                $netsql .= "value('$netid','$name','$cluster','$user','$group','$mode','$swname','','')";
            }
            $netresult = $mysql->query($netsql);
            if($netresult['result']===true){
                $rmq = new RabbitMqexecute();
                $msg = json_encode($rmq_msg);
                foreach($noderes['result'] as $value){
                    $rmq->sendmsg($msg, 'vnet',$value['nodes'],$value['nodes'].'server');
                }
                return json_encode('{"status":"200"}');
            }else{
                // 这儿需要记录错误日志,需要log类
                $data['status'] = 402;
                $data['error'] = 'Add new network error';
                $data['sql'] = $netsql;
                $data['res'] = $netresult;
                return json_encode($data);
            }
        }elseif($mode =='vxlan' && $vxlan_model){
            $vxlan_nic = config('config.network.vxlan_map_interface');
            $vxlan_drive = config('config.network.vxlan_drive');
            return json_encode(-1);
        }else{
            $data['status'] = 402;
            $data['error'] = 'The new added network model is error ';
            return json_encode($data);
        }
    }
    public function update($id){
        //PUT
        $method = strtolower(request()->get_method());
        $hearder = request()->get_header();
        if($method == 'put'){
            return true;
        }
    }
    public function del($id){
        //DELETE
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method == 'delete'){
            if($header['content-type'] =='application/json'){
                $request = json_decode(request()->get_rawbody(),true);
            }
        }
        $id = $id[0];
        $user = $request['user'];
        if(empty($id)||empty($user)){
            return json_encode('{"402":"Parameter error"}');
        }
        //  取得网络名下的子网信息
        $netsql = "select a.network_id,a.groups,a.brname,a.network_mode,a.dhcpnamespace,a.routenamespace,b.subnet_id,b.vlan_id,b.subnet_name ";
        $netsql .= " from vnet_networks a inner join vnet_subnets b on a.network_id=b.network_id and a.users=b.users and a.groups=b.groups where a.id='$id' and a.users='$user'";
        $mysql = new Mysql();
        $res = $mysql->query($netsql);
        // 如果网络名下存在子网信息,遍历子网名称，
        if($res['result']){
            //  取得network基本信息，包括network id,组名称,subnet id, 如果network id和组名称不是唯一，删除网络失败
            foreach($res['result'] as $value){
                $network_id[] = $value['network_id'];
                $owner_group[] = $value['groups'];
                $brname[] = $value['brname'];
                $network_mode[] = $value['network_mode'];
                $dhcpnamespace[] = $value['dhcpnamespace'];
                $routenamespace[] =$value['routenamespace'];
            }
            if(count(array_unique($network_id))===1 && count(array_unique($owner_group))===1 && count(array_unique($brname))===1 && count(array_unique($dhcpnamespace))===1 && count(array_unique($routenamespace))===1 && count(array_unique($network_mode))===1){
                $netid = implode(",",array_unique($network_id));
                $group = implode(",",array_unique($owner_group));
                $swname = implode(",",array_unique($brname));
                $mode = implode(",",array_unique($network_mode));
                $dhcp_namespace = implode(",",array_unique($dhcpnamespace));
                $route_namespace = implode(",",array_unique($routenamespace));
            }else{
                $data['statis'] = 402;
                $data['error'] = 'The network information are inconsistent';
                $data['sqlres'] = $res;
                return json_encode($data);
            }
            //  处理所有子网，IP地址使用情况等
            foreach($res['result'] as $value){
                $subid = $value['subnet_id'];
                $vlanid = $value['vlan_id'];
                $subname = $value['subnet_name'];
                // get subid表中IP地址的使用情况
                $subinfo = new Subinfo();
                $get_ip_used_sql = "select ip,mask from $subid where stat>='1'";
                $get_ip_used_res = $subinfo->query($get_ip_used_sql);
                // 取得ports表中port端口的使用情况
                $get_ports_used_sql = "select portmessage from vnet_ports where network_id='$netid' and subnet_id='$subid'";
                $get_ports_used_res = $mysql->query($get_ports_used_sql);
                // 取得VDHCPS下的子网列表
                $get_vdhcp_sql = "select dhcp_msg from vnet_dhcps where network_id='$netid' and subnet_id='$subid'";
                $get_vdhcp_res = $mysql->query($get_vdhcp_sql);
                foreach($get_vdhcp_res['result'] as $value){
                    $dhcp_msg[] = $value['dhcp_msg'];
                }
                // 对比已分配的IP地址使用情况 , 如果有已使用的IP地址不是DHCP地址，删除网络失败
                if(!$get_ip_used_res['result']){
                    $data['statis'] = 402;
                    $data['error'] = 'The subnet IP address information are error';
                    $data['sql'] = $get_ip_used_sql;
                    $data['sqlres'] = $get_ip_used_res;
                    return json_encode($data);
                }
                $list_ip = [];
                $used_ip = [];
                foreach($get_ip_used_res['result'] as $ip){
                    $list_ip[] = $ip['ip'];
                    foreach($get_ports_used_res['result'] as $key=>$msg){
                        $msg_res = json_decode($msg['portmessage'],true);
                        if($msg_res && in_array($ip['ip'],$msg_res)&& $msg_res['how_use'] =='DHCP'){
                            $used_ip[] = $ip['ip'];
                        }
                    }
                    $used_ip = array_flip($used_ip);
                    $used_ip = array_flip($used_ip);
                    $used_ip = array_values($used_ip);
                }
                if(strcasecmp(json_encode($list_ip),json_encode($used_ip)) !== 0){
                    $data['statis'] = 402;
                    $data['error'] = 'The subnet used IP address information are inconsistent';
                    $data['sql'] = $get_ip_used_sql;
                    $data['sqlres'] = $get_ip_used_res;
                    return json_encode($data);
                }
                //  如果子网已分配的IP地址，属于DHCP服务器，生成删除设备的消息队列的信息
                $rmq_msg['network'] = 'del';
                $rmq_msg['brname'] = $swname;
                $rmq_msg['network_mode'] =$mode;
                $rmq_msg['net_id'] =$netid;
                $rmq_msg['dhcp_namespace'] =$dhcp_namespace;
                $rmq_msg['route_namespace'] =$route_namespace;
                $rmq_msg[$subname] =[
                    'sub_id' => $subid,
                    'vlan_id' => $vlanid
                ];
                // 生成删除SUBNET的SQL 
                $sub_sql[] = "delete from vnet_subnets where network_id='$netid' and subnet_id='$subid' and groups='$group'";
                $port_sql[] = "delete from vnet_ports where subnet_id='$subid'";
                $vdhcp_sql[] = "delete from vnet_dhcps where network_id='$netid' and subnet_id='$subid'";
                $drop_sql[] = "drop table $subid";
            }
            // 发送删除设备的信息到消息队列
            foreach($dhcp_msg as $node_msg){
                $node_list[] = json_decode($node_msg,true)['my_ip'];
            }
            $node_list = array_unique($node_list);
            $rmq = new RabbitMqexecute();
            $msg = json_encode($rmq_msg);
            foreach($node_list as $node){
                $rmq->sendmsg($msg, 'vnet',$node,$node.'server');
            }
            $mysql->begin();
            for($i=0;$i<count($sub_sql);$i++){
                $sub_del_res[] = $mysql->query($sub_sql[$i]);
                $port_del_res[] = $mysql->query($port_sql[$i]);
                $vdhcp_del_res[] = $mysql->query($vdhcp_sql[$i]);
                if(!$sub_del_res[$i]['result'] || !$port_del_res[$i]['result'] || !$vdhcp_del_res[$i]['result']){
                    $mysql->rollback();
                    $data['statis'] = 402;
                    $data['error'] = 'Delete subnet error';
                    $data['sqlres'] = $sub_del_res;
                    return json_encode($data);
                }
            }
            foreach($drop_sql as $drop_tab_sql){
                $subinfo->query($drop_tab_sql);
            }
            $mysql->commit();
            // 删除子网后,删除网络,dhcp信息,route等
            $netdelsql = "delete from vnet_networks where id='$id'";
            $mysql->begin();
            $netdelres = $mysql->query($netdelsql);
            if($netdelres['result']){
                $mysql->commit();
                return json_encode('{"status":"200"}');
            }else{
                $mysql->rollback();
                $data['statis'] = 402;
                $data['error'] = 'Delete network error';
                $data['sql'] = $netdelsql;
                $data['sqlres'] = $netdelres;
                return json_encode($data);
            }
        }else{
            // 如果网络名下没有子网,直接删除这个网络,网络服务器节点上的虚拟交换机，namespace
            $getnetsql = "select network_id,network_mode,brname,dhcpnamespace,routenamespace from vnet_networks where id='$id'";
            $getnetres = $mysql->query($getnetsql);
            if(!$getnetres['result']){
                // 这儿需要记录错误日志，需要log类
                $data['statis'] = 402;
                $data['error'] = 'Network does not exists';
                $data['sql'] = $getnetsql;
                $data['sqlres'] = $getnetres;
                return json_encode($data);
            }
            foreach($getnetres['result'] as $value){
                $rmq_msg = ['network'=>'del','brname'=>$value['brname'],'network_mode'=>$value['network_mode'],'dhcp_namespace'=>$value['dhcpnamespace'],'router_namespace'=>$value['routenamespace']];
            }
            $getnodesql = "select nodes,dhcp,router from vnet_nodes where uses='server'";
            $noderes = $mysql->query($getnodesql);
            $rmq = new RabbitMqexecute();
            $msg = json_encode($rmq_msg);
            foreach($noderes['result'] as $value){
                $rmq->sendmsg($msg, 'vnet',$value['nodes'],$value['nodes'].'server');
            }
            $netdelsql = "delete from vnet_networks where id='$id'";
            $netdelres = $mysql->query($netdelsql);
            if($netdelres['affected_rows'] === 1){
                return json_encode('{"status":"200"}');
            }else{
                // 这儿需要记录错误日志，需要log类
                $data['status']=402;
                $data['error'] = 'Delete Network error';
                $data['sql'] = $netdelsql;
                $data['res'] = $netdelres;
                return json_encode($data);
            }
        }
    }
}
