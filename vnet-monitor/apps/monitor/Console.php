<?php
namespace monitor;

class Console{
    public function execute(){
        while(true){
        echo __METHOD__.' this class is';
        sleep(3);
        }
    }
}
