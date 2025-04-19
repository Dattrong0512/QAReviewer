<?php

class App
{
    protected $controller = "Home";
    protected $action = "Show";
    protected $params = [];

    function __construct()
    {
        $urlData = $this->UrlProcess();

        $urlParts = $urlData['urlParts'] ?? [];
        $queryParams = $urlData['queryParams'] ?? [];

        //Handle controller
        if (!empty($urlParts) && file_exists("./mvc/controllers/" . $urlParts[0] . ".php")) {
            $this->controller = $urlParts[0];
            unset($urlParts[0]);
        }
        require_once "./mvc/controllers/" . $this->controller . ".php";
        $this->controller = new $this->controller;

        //Handle Action

        if (isset($urlParts[1])) {
            if (method_exists($this->controller, $urlParts[1])) {
                $this->action = $urlParts[1];
            }
            unset($urlParts[1]);
        }

        $this->params = array_merge($urlParts ? array_values($urlParts) : [], $queryParams);

        call_user_func_array([$this->controller, $this->action], [$this->params]);
    }



    function UrlProcess()
    {
        $urlParts = [];
        $queryParams = $_GET;

        if (isset($_GET["url"])) {
            $urlParts = explode("/", filter_var(trim($_GET["url"], "/"))); // Sửa $_GET("url") thành $_GET["url"]
            unset($queryParams["url"]);
        }
        return [
            'urlParts' => $urlParts,
            'queryParams' => $queryParams
        ];
    }
}
