<?php
namespace Vnet;
use Vnet\Singleton;
//define('LOG_PATH','/opt/cloudswoole/runtime/');
class Log{
    Use Singleton;
    // The configure of log 
    protected static $config=[];
    // The content of log 
    protected static $log = [];
    // The type of log 
    protected static $level = ['debug', 'info', 'sql', 'notice', 'alert','error','critical'];
    // The file that store log content
    protected static $file;

    //
    public function write($msg,$level){
        //log content
        $log_msg = $level.'--'.date("Ymd h:i:s").'-->' .$msg."\n";
        if(!in_array($level,self::$level)) return false;
        $this->save($log_msg,$level);
    }
    //
    public function save($msg,$level){
        $file = self::get_log_file();
        if($file){
            swoole_async_writefile($file,$msg,NULL,FILE_APPEND);
        }
    }
    // Get Log file
    private static function get_log_file(){
        $log_path  = LOG_PATH.'logs';
        file_exists($log_path) OR mkdir($log_path,0755,true);
        if(!is_dir($log_path) OR ! self::chk_writable($log_path)){
            return false;
        }
        $log_file = $log_path.DIRECTORY_SEPARATOR.date('Ymd').'.log';
        if (!self::chk_writable($log_file)){
            return false;
        }else{
            return $log_file;
        }
    }
    //check whether a file or directory can write
    private static function chk_writable($file) {
        if (is_dir($file)){
            $dir = $file;
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        }
        return $writeable;
    }
}
