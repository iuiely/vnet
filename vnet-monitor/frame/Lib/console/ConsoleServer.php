<?php
//http server
namespace Vnet\console;
use Vnet\Singleton;

class ConsoleServer {
    USE Singleton;
    public function runAction($config = []){
        $this->on_process($config);
    }
    protected function on_process($config){
        $daemon_config=$config['server'];
        $module = $config['default']['module'];
        $method = $config['default']['method'];
        $argvs = $config['default']['parameter'];
        $classname = $module.'\\'.ucfirst($config['default']['class']);
        $class = new $classname;
        Daemon::getInstance($daemon_config)->addtask(array(
            'function' => array($class,$method),
            'argvs' =>$argvs
        ));
        Daemon::getInstance()->start();  
    }
}
