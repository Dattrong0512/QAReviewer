<?php
class Controller{

    public function model($model, ...$params){
        require_once "./mvc/models/".$model.".php";
        return new $model(...$params);
    }

    public function view($view, $data=[]){
        require_once "./mvc/views/".$view.".php";
    }

}
?>