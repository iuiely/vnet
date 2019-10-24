<?php
namespace server;
class Test{
    public function test($data,$queue){
/*
        for($i=0;$i<10;$i++){
            $sql = "select * from test.user where id=1";
            $conn = new \mysqli('10.200.3.97','root','123456','test');
            $res = $conn->query($sql);
            $row = $res->fetch_assoc();
            file_put_contents('/tmp/demo.txt', date("Y-m-d H:i:s", time()) ." ".$row['id']." ".$row['name']. "\n", FILE_APPEND);
            sleep(4);
        }
*/
        $msg=$data->getBody();
        $dataID = $data->getDeliveryTag();
        file_put_contents("/tmp/demo.txt", $msg.'|'.$dataID.''."\n",FILE_APPEND);
        $queue->ack($data->getDeliveryTag());
    }
}
