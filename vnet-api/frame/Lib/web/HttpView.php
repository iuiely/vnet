<?php
namespace Vnet\web;

class HttpView{
    public $title;
    public $ViewPath;

    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
        return $this;
    }

    public function render($template, $data){
        extract($data);
        $template=str_replace('controller','view',$template);
        $__filepath__ =  Config::getInstance()->get('http.app_path').DIRECTORY_SEPARATOR.$template.'.php';
        if (!is_file($__filepath__)) {
            throw new Exception("视图文件不存在：{$__filepath__}");
        }
        ob_start();
        ob_implicit_flush(0);

        include $__filepath__;
        $content = ob_get_clean();
        return $content;
    }
}
