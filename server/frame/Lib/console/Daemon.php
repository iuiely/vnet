<?php
namespace Vnet\console;
use Vnet\HandleFilesystem;
class Daemon{
    private static $_instance = null;
    // the file that store the pid number of the process
    protected $PidFile;
    // the directory path that store the pidfile 
    protected $StorePath;
    protected $task;
    protected $logfile;
    protected $Title;
    protected $config=[
                  'service' => 'Daemon_lib',
                  'pid_file'=>'/tmp/Daemon.pid',
                  'log_file'=>'/tmp/Daemon.log'
              ];
    private function __clone(){}
    private function __wakeup(){}
    private function __construct(array $config=null){
        if(!empty($config)){
            $this->config = array_merge($this->config,$config);
        }
        $this->PidFile=$this->config['pid_file'];
        $this->logfile = $this->config['log_file'];
        $this->Title = $this->config['service'];
    }

    //公有化获取实例方法
    public static function getInstance(array $config=null){
        $class = __CLASS__;
        if (!(self::$_instance instanceof $class)){
            self::$_instance = new $class($config);
        }
        return self::$_instance;
    }
    protected function set_storepath($dir=null){
        if(is_dir($dir)){
            $this->StorePath=$dir;
        }else{
            $this->StorePath=__DIR__;
        }
    }
    private function execute_deamon(){
        // check environment
        if(php_sapi_name() != 'cli'){
            die('This script need run in CLI');
        }
        !function_exists('pcntl_signal') && die('Error:Need PHP Pcntl extension!');
        $pid1 = pcntl_fork();
        if($pid1 >0){
            exit;
        }elseif($pid1 <0){
            exit("The first time fork child process error");
        }
        if(posix_setsid() == -1){
            die("$this->Title start failed");
        }
        $pid2 = pcntl_fork();
        if($pid2 >0){
            exit;
        }elseif($pid2 <0){
            exit("The second time fork child process error");
        }
        chdir('/');
        umask(0);
        HandleFilesystem::getInstance()->Add_file($this->PidFile);
        $fp = fopen($this->PidFile,'w')or die("Can't create pid file");
        fwrite($fp,posix_getpid());
        fclose($fp);
        // close file descriptor
        global $STDOUT, $STDERR;
        @fclose(STDIN);
        $this->handle_io_stream();
        cli_set_process_title($this->Title);
        if(!empty($this->task)){
            foreach($this->task as $_task){
                if(!empty($_task['argv'])){
                    call_user_func($_task['function'], $_task['argv']);
                }else{
                    call_user_func($_task['function']);
                }
            }
        }
    }
    protected function handle_io_stream(){
        HandleFilesystem::getInstance()->Add_file($this->logfile);
        $STDOUT = fopen($this->logfile, 'ab+');
        $STDERR = fopen($this->logfile, 'ab+');
        eio_dup2($STDOUT, STDOUT);
        eio_dup2($STDERR, STDERR);
        eio_event_loop();
        fclose($STDOUT);
        fclose($STDERR);
    }
    protected function get_pid(){
        if(!file_exists($this->PidFile)) return 0;
        $pid = intval(file_get_contents($this->PidFile));
        if(posix_kill($pid,SIG_DFL)){
            return $pid;
        }else{
            unlink($this->PidFile);
            return 0;
        }
    }
    public function addtask($task=array()){
        if (!isset($task['function']) || empty($task['function'])) {
            $this->usemsg('The task need function parameter');
        }
        if (!isset($task['argv']) || empty($task['argv'])) {
            $task['argv'] = "";
        }
        $this->task[] = $task;
    }
    private function usemsg($message){
        printf("%s  %d %d  %s" . PHP_EOL, date("Y-m-d H:i:s"), posix_getpid(), posix_getppid(), $message);
    }
    public  function start(){
        if($this->get_pid() >0){
            $this->usemsg("The $this->Title already Running");
        }else{
            $this->usemsg("The $this->Title start");
            $this->execute_deamon();
        }
    }
    public function stop(){
        $pid = $this->get_pid();
        if($pid >0){
            posix_kill($pid, SIGTERM);
            unlink($this->PidFile);
            $this->usemsg("The $this->Title Stop");
        }else{
            $this->usemsg("The $this->Title does not Running");
        }
    }
    public function status() {
        if ($this->get_pid() > 0) {
            $this->usemsg("The $this->Title already Running");
        }else{
            $this->usemsg("The $this->Title does not Running");
        }
    }
}
