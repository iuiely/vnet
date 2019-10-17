<?php
namespace api\controller;
use Vnet\db\Mysql;
use Vnet\db\Subinfo;

class Fix { 
    public function show(){
        return json_encode(-1);
    }
    public function create(){
        // POST
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method == 'post'){
            if($header['content-type'] =='application/json'){
                $request = json_decode(request()->get_rawbody(),true);
            }
        }        
        $user = $request['user'];
        $cluster = $request['cluster'];
        $network = $request['network'];
        $subnet = $request['subnet'];
        $ipaddr = isset($request['ip'])?$request['ip']:"";
        if(empty($user)||empty($cluster)||empty($network)||empty($subnet)){
            return json_encode('{"402":"Parameter error"}');
        }
        $mysql =  new Mysql();
        // 取得子网ID,DHCP range
        $subsql = "select a.network_id,a.brname,a.network_mode,b.subnet_id,b.vlan_id from ";
        $subsql .= "  vnet_networks a inner join vnet_subnets b on a.network_id=b.network_id and a.clusters=b.clusters where a.network_name='$network' and b.subnet_name='$subnet'";
        $subres = $mysql->query($subsql);
        if(count($subres['result'])!==1){
            $data['status'] = 402;
            $data['error'] = 'The Number of networks or subnets error';
            return json_encode($data);
        }
        $netid = $subres['result']['0']['network_id'];
        $brname = $subres['result']['0']['brname'];
        $mode = $subres['result']['0']['network_mode'];
        $subid = $subres['result']['0']['subnet_id'];
        $vlanid = $subres['result']['0']['vlan_id'];
        // 查找子网IP池可用IP地址
        $subinfo = new Subinfo();
        $mysql->begin();
        $useableipsql = "select id,ip,mac from $subid where stat='-1' and ip='$ipaddr' for update";
        $useableipres = $subinfo->query($useableipsql);
        if($ipaddr && count($useableipres['result'])===1 && $useableipres['result']['0']['ip']=="$ipaddr"){
            $id = $useableipres['result']['0']['id'];
            $portid = substr(sha1(md5(uniqid(microtime(true),true))),0,16);
            $portname = "vnet-".substr(sha1(md5(uniqid(microtime(true),true))),0,6);
            $portmessage ='{"portname":"'.$portname.'","ip":"'.$ipaddr.'","mac":"'.$useableipres['result']['0']['mac'].'","how_use":"FIX"}';
            $portvalues = "('".$netid."','".$subid."','".$portid."','".$portmessage."')";
            $vmvalue['brname'] = $brname;
            $vmvalue['mode'] = $mode;
            $vmvalue['portid'] = $portid;
            $vmvalue['portname'] = $portname;
            $vmvalue['ip'] = $ipaddr;
            $vmvalue['mac'] = $useableipres['result']['0']['mac'];
            $vmvalue['vlanid'] = $vlanid;
            $useablestatsql = "update $subid set stat='1' where id='$id'";
            $newportsql = "insert into vnet_ports(network_id,subnet_id,portid,portmessage)values".$portvalues;
            $useablestatres = $subinfo->query($useablestatsql);
            $newportres = $mysql->query($newportsql);
            if($useablestatres['affected_rows'] == '1' && $newportres['affected_rows'] == '1'){
                $vmvalue['id'] = $newportres['insert_id'];
                $mysql->commit();
                return json_encode($vmvalue);
            }else{
                $mysql->rollback();
                // need to log the error 
                $data['status'] = 402;
                $data['error'] = 'Assign IP address or port error';
                return json_encode($data);
            }
        }elseif(!$ipaddr){
            $portid = substr(sha1(md5(uniqid(microtime(true),true))),0,16);
            $portname = "vnet-".substr(sha1(md5(uniqid(microtime(true),true))),0,8);
            $portmessage ='{"portname":"'.$portname.'","ip":"","mac":"","how_use":"FIX"}';
            $portvalues = "('".$netid."','".$subid."','".$portid."','".$portmessage."')";
            $vmvalue['brname'] = $brname;
            $vmvalue['mode'] = $mode;
            $vmvalue['portid'] = $portid;
            $vmvalue['portname'] = $portname;
            $vmvalue['ip'] = $ipaddr;
            $vmvalue['mac'] = "";
            $vmvalue['vlanid'] = $vlanid;
            $newportsql = "insert into vnet_ports(network_id,subnet_id,portid,portmessage)values".$portvalues;
            $newportres = $mysql->query($newportsql);
            if($newportres['affected_rows'] == '1'){
                $vmvalue['id'] = $newportres['insert_id'];
                $mysql->commit();
                return json_encode($vmvalue);
            }else{
                $mysql->rollback();
                // need to log the error 
                $data['status'] = 402;
                $data['error'] = 'Assign port error';
                return json_encode($data);
            }
        }else{
                // need to log the error 
            $data['status'] = 402;
            $data['error'] = 'Create Port error,The IP address error ';
            return json_encode($data);
        }
    }
    public function update($id){
        //PUT
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method == 'put'){
            if($header['content-type'] =='application/json'){
                $request = json_decode(request()->get_rawbody(),true);
            }
        }
        date_default_timezone_set("Asia/Shanghai");
        $id=$id[0];
        $user = $request['user'];
        $cluster = $request['cluster'];
        $network = $request['network'];
        $subnet = $request['subnet'];
        //$port = $request['portid'];
        $ipaddr = $request['ip'];
        if(empty($user)||empty($cluster)||empty($network)||empty($subnet)||is_array($user)||is_array($cluster)||is_array($network)||is_array($subnet)||is_array($ipaddr)){
            return json_encode('{"402":"Parameter error"}');
        }
        $datasql = "select a.subnet_id,a.portmessage,b.brname,b.network_mode,c.vlan_id from vnet_ports a inner join vnet_networks b on a.network_id=b.network_id ";
        $datasql .= "  inner join vnet_subnets c on b.clusters=c.clusters and a.subnet_id=c.subnet_id where b.network_name='$network' and c.subnet_name='$subnet' and a.id='$id'";
        $mysql = new Mysql();
        $datares = $mysql->query($datasql);
        if(count($datares['result'])===1 && json_decode($datares['result']['0']['portmessage'],true)['ip'] && json_decode($datares['result']['0']['portmessage'],true)['how_use'] =='FIX'){
            $subid = $datares['result']['0']['subnet_id'];
            $portname = json_decode($datares['result']['0']['portmessage'],true)['portname'];
            $oldip = json_decode($datares['result']['0']['portmessage'],true)['ip'];
            $oldmac = json_decode($datares['result']['0']['portmessage'],true)['mac'];
            $newmacsql = "select mac from $subid where ip='$ipaddr' and stat='-1'";
            $subinfo = new Subinfo();
            $mysql->begin();
            $newmacres = $subinfo->query($newmacsql);
            if(!$newmacres['result']){
                $mysql->rollback();
                // need to log the error 
                $data['status'] = 402;
                $data['error'] = 'Check port or IP address error';
                return json_encode($data);
            }
            $portmessage ='{"portname":"'.$portname.'","ip":"'.$ipaddr.'","mac":"'.$newmacres['result']['0']['mac'].'","how_use":"FIX"}';
            $m_oldipsql = "update $subid set stat='-1' where ip='$oldip' and mac='$oldmac'";
            $n_newipsql = "update $subid set stat='1' where ip='$ipaddr'";
            $m_portsql = "update vnet_ports set portmessage='$portmessage' where id='$id'";
            $vmvalue['brname'] = $datares['result'][0]['brname'];
            $vmvalue['mode'] = $datares['result'][0]['network_mode'];
            $vmvalue['portname'] = $portname;
            $vmvalue['ip'] = $ipaddr;
            $vmvalue['mac'] = $newmacres['result']['0']['mac'];
            $vmvalue['vlanid'] = $datares['result'][0]['vlan_id'];
            $vmvalue['id'] = $id;
            $m_oldipres=$subinfo->query($m_oldipsql);
            $n_newipres=$subinfo->query($n_newipsql);
            $m_portres=$mysql->query($m_portsql);
            if($m_oldipres['affected_rows']=='1' && $n_newipres['affected_rows']=='1' && $m_portres['affected_rows']=='1'){
                $mysql->commit();
                return json_encode($vmvalue);
            }else{
                $mysql->rollback();
                // need to log the error 
                $data['status'] = 402;
                $data['error'] = 'Assign new port or IP address error';
                return json_encode($data);
            }
        }elseif(count($datares['result'])===1 && !json_decode($datares['result']['0']['portmessage'],true)['ip'] && json_decode($datares['result']['0']['portmessage'],true)['how_use'] =='FIX'){
            $subid = $datares['result']['0']['subnet_id'];
            $portname = json_decode($datares['result']['0']['portmessage'],true)['portname'];
            $newmacsql = "select mac from $subid where ip='$ipaddr' and stat='-1'";
            $subinfo = new Subinfo();
            $mysql->begin();
            $subinfo->begin();
            $newmacres = $subinfo->query($newmacsql);
            if(!$newmacres['result']){
                // need to log the error 
                $data['status'] = 402;
                $data['error'] = 'Check port or IP address error';
                return json_encode($data);                
            }
            $portmessage ='{"portname":"'.$portname.'","ip":"'.$ipaddr.'","mac":"'.$newmacres['result']['0']['mac'].'","how_use":"FIX"}';
            $n_newipsql = "update $subid set stat='1' where ip='$ipaddr'";
            $m_portsql = "update vnet_ports set portmessage='$portmessage' where id='$id'";
            $vmvalue['brname'] = $datares['result'][0]['brname'];
            $vmvalue['mode'] = $datares['result'][0]['network_mode'];
            $vmvalue['portname'] = $portname;
            $vmvalue['ip'] = $ipaddr;
            $vmvalue['mac'] = $newmacres['result']['0']['mac'];
            $vmvalue['vlanid'] = $datares['result'][0]['vlan_id'];
            $vmvalue['id'] = $id;
            $n_newipres=$subinfo->query($n_newipsql);
            $m_portres=$mysql->query($m_portsql);
            if($n_newipres['affected_rows']=='1' && $m_portres['affected_rows']=='1'){
                $mysql->commit();
                $subinfo->commit();
                return json_encode($vmvalue);
            }else{
                $mysql->rollback();
                $subinfo->rollback();
                // need to log the error 
                $data['status'] = 402;
                $data['error'] = 'Assign port or new IP address error';
                return json_encode($data);
            }
        }else{
            // need to log the error 
            $data['status'] = 402;
            $data['error'] = 'Check subnet information error';
            return json_encode($data);
        }
    }
    public function del($id){
        //DELETE
        $method = strtolower(request()->get_method());
        $header = request()->get_header();
        if($method != 'delete'){
            return json_encode('{"status":"405","error":"Method Not Allowed"}');
        }
        if($header['content-type'] =='application/json'){
            $request = json_decode(request()->get_rawbody(),true);
        }
        $id=$id[0];
        $user = $request['user'];
        $cluster = $request['cluster'];
        $network = $request['network'];
        $subnet = $request['subnet'];
        if(empty($user)||empty($cluster)||empty($network)||empty($subnet)||empty($id)){
            $data['status'] = 402;
            $data['error'] = 'Parameter error';
            return json_encode($data);
        }
        $mysql = new Mysql();
        $subsql = "select b.subnet_id from vnet_networks a inner join vnet_subnets b on a.network_id=b.network_id and a.clusters=b.clusters ";
        $subsql .= " where a.network_name='$network' and b.subnet_name='$subnet'";
        $subres = $mysql->query($subsql);
        if(!$subres['result']){
            $data['status'] = 402;
            $data['error'] = 'Network or subnet error';
            return json_encode($data);
        }
        $subid = $subres['result']['0']['subnet_id'];
        $portinfosql = "select portmessage from vnet_ports where id='$id'";
        $portinfores = $mysql->query($portinfosql);
        if($portinfores['result']){
            $portmessage = json_decode($portinfores['result']['0']['portmessage'],true);
            $ip = $portmessage['ip'];
            $mac = $portmessage['mac'];
        }
        $delportssql = "delete from vnet_ports where id='$id'";
        $subinfo = new Subinfo();
        $updatesubtabsql = "update $subid set stat='-1' where ip='$ip' and mac='$mac'";
        $mysql->begin();
        $subinfo->begin();        
        $updatesubtabres = $subinfo->query($updatesubtabsql);
        $delportsres = $mysql->query($delportssql);
        if($delportsres['result']&&$updatesubtabres['result']){
            $mysql->commit();
            $subinfo->commit();
            return json_encode('{"status":"200"}');
        }else{
            $mysql->rollback();
            $subinfo->commit();
            $data['status'] = 402;
            $data['error'] = 'Delete port or Ip address error';
            return json_encode($data);            
        }
    }
}
