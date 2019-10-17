<?php
namespace Vnet;
use Vnet\Singleton;

class HandleFilesystem{
    use Singleton;
    public function Deldir($dirname){
        if($handle = opendir($dirname)){
            while (false !== ($item = readdir($handle))){
                if ($item != '.' && $item != '..' ){
                    if(is_dir("$dirname/$item" ) ) {
                       $this->Deldir($dirname.'/'.$item);
                    }else{
                        if(!unlink($dirname.'/'.$item)){
                            return false;
                        }
                    }
                }
            }
        }
        closedir($handle);
        if(!rmdir($dirname)){
            return false;
        }
    }
    public function Delfile($filename){
        if(is_file($filename)){
            if(!unlink($filename)){
                return false;
            }
        }
    }
    public function Add_dir($dirname){
        if(!file_exists($dirname)){
            mkdir($dirname,0755,true);
            return file_exists($dirname);
        }else{
            return true;
        }
    }
    public function Add_file($filename){
        if(file_exists($filename)){
            return true;
        }
        if(!is_dir(dirname($filename))){
            $this->Add_dir(dirname($filename));
            fopen($filename,'w');
            return file_exists($filename);
        }else{
            fopen($filename,'w');
            return file_exists($filename);
        }
    }
}
