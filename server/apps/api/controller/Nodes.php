<?php
namespace api\controller;
use Vnet\db\Mysql;

class Nodes{
    public function view($user="admin",$draw=0,$start=0,$length=15,$findnode=""){
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
            $findnode = $request['findnode'];            
        }else{
            $draw = $draw;
            $start = $start;
            $length = $length;
            $user = $user;
            $findnode = $findnode;            
        }
        if($user=="admin" && $findnode ==""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_nodes";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_nodes limit $start,$length";
            $result1 = $mysql->query($sql1);
            $data = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $result1['result']
            );
            return json_encode($data);
        }elseif($user=="admin" && $findnode !=""){
            $mysql = new Mysql();
            $sql = "select count(id) as row from vnet_nodes where nodes like '%$findnode%'";
            $result = $mysql->query($sql);
            $totalData = $result['result']['0']['row'];
            $totalFiltered = $totalData;
            $sql1 = "select * from vnet_nodes where nodes like '%$findnode%' limit $start,$length";
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
        if(empty($request) && $request['user'] !='admin'){
            return json_encode(-1);
        }
        $id = $id[0];
        $sql = "select * from vnet_nodes where id='$id'";
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
        $node = $request['node'];
        $uses = $request['uses'];
        $dhcp = $request['dhcp'];
        $router = $request['router'];
        $stat = $request['stat'];
        if(empty($node)||empty($uses)||empty($stat)){
            return json_encode('{"status":"402","error":"Parameter error"}');
        }
        $mysql = new Mysql();
        $sql = "insert into vnet_nodes(nodes,uses,dhcp,router,stat)value('$node','$uses','$dhcp','$router','$stat')";
        $result = $mysql->query($sql);
        if($result['result']===true){
            return json_encode('{"status":"200"}');
        }else{
            $data['status'] =402;
            $data['error'] = 'Add new node error';
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
        $uses = $request['uses'];
        $dhcp = $request['dhcp'];
        $router = $request['router'];
        $stat = $request['stat'];
        $mysql = new Mysql();
        $sql = "update vnet_nodes set uses='$uses',dhcp='$dhcp',router='$router',stat='$stat'";
        $mysql->begin();
        $result = $mysql->query($sql);
        if($result['affected_rows']===1){
            $mysql->commit();
            return json_encode('{"status":"200"}');
        }else{
            $mysql->rollback();
            $data['status'] =402;
            $data['error'] = 'change node error';
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
        $id=$id[0];
        $sql = "delete from vnet_nodes where id='$id'";
        $mysql = new Mysql();
        $mysql->begin();
        $result = $mysql->query($sql);
        if($result['affected_rows']===1){
            $mysql->commit();
            return json_encode('{"status":"200"}');
        }else{
            $mysql->rollback();
            $data['status'] =402;
            $data['error'] = 'Delete node error';
            return json_encode($data);
        }
    }
}
