<?php
//http server
namespace Vnet\web;
use Vnet\Singleton;

class HttpServer {
    USE Singleton;
    private static $instance;
    private $httpserver;

    private $service_name ;
    private $host ;
    private $port ;
    private $pid_file ;
    
    public function runAction($config = []){
        $this->service_name = $config['api']['service'];
        $this->host = $config['api']['ip'];
        $this->port = $config['api']['port'];
        $this->pid_file = $config['api']['pid_path'].DIRECTORY_SEPARATOR.$this->service_name.".pid";      

        $this->httpserver = new \Swoole\Http\Server($this->host,$this->port);
        $this->httpserver->set($config['api']['set']);
        $this->welcome($this->host,$this->port);
        $this->onStart();
        $this->onShutDown();
        $this->onManagerStart();
        $this->onWorkerStart();
        $this->onTask();
        $this->onRequest();
        $this->httpserver->start();
    }
    protected function onStart(){
        $this->httpserver->on('start',function($server){
            //date_default_timezone_set(config('app.timezone') ?? 'Asia/Shanghai');
            date_default_timezone_set('Asia/Shanghai');
            if (function_exists('cli_set_process_title')) {
                @cli_set_process_title($this->service_name ."-master");
            }else{
                @swoole_set_process_name($this->service_name ."-master");
            }
            $pid = [$server->master_pid,$server->manager_pid];
            file_put_contents($this->pid_file,implode(',',$pid));
        });
    }
    protected function onManagerStart(){
        $this->httpserver->on('ManagerStart',function($server){
            if (function_exists('cli_set_process_title')) {
                @cli_set_process_title($this->service_name."-manager");
            }else{
                @swoole_set_process_name($this->service_name."-manager");
            }
        });
    }
    protected function onWorkerStart(){
        $this->httpserver->on('WorkerStart',function($server,$worker_id){
            if($worker_id >=$server->setting['worker_num']){
                @swoole_set_process_name($this->service_name."-task_{$worker_id}");
            }else{
                @swoole_set_process_name($this->service_name."-worker_{$worker_id}");
            }
        });
    }
    protected function onReceive(){
        $this->httpserver->on('receive',function($server,$fd,$from_worker_id,$data){
        });
    }
    protected function onTask(){
        $this->httpserver->on('task',function($server, $task_id, $from_worker_id, $data){
        });
    }
    protected function onRequest(){
        $this->httpserver->on('request',function($request,$response){
            HttpApplication::getInstance()->http_run($this->httpserver,$request,$response);
        });
    }
    protected function onShutDown(){
        $this->httpserver->on('shutDown',function($server){
            echo "shutdown\n";
        });
    }
    protected function welcome($host,$port){
        $swoole_version = swoole_version();
        $php_version = phpversion();
        echo <<<EOT
SERVER NAME           :  Cloud swoole
PHP VERSION           :  {$php_version}
SWOOLE VERSION        :  {$swoole_version}
LISTEN ADDR           :  {$host}
LISTEN PORT           :  {$port}\n
EOT;
    }
}
