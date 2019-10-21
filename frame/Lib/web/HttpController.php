<?php
namespace Vnet\web;

class HttpController{
    protected $view;

    public function __construct(){
        $this->view  = new HttpView();
    }


    final public function json($array=array(),$callback=null){
        $json = json_encode($array);
        $json = is_null($callback) ? $json : "{$callback}({$json})" ;
        return  $json;
    }

    protected function display($name, $value = ''){
        if(strpos($name,'/')===false){
            $name=str_replace('\\',DIRECTORY_SEPARATOR,get_class($this)).DIRECTORY_SEPARATOR.$name;
        }
        return $this->view->render($name,$value);
    }
}
