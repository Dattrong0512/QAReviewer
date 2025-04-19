<?php

 class Home extends Controller
 {
    public function Show()
    {   
        $view = $this->view("Layout/MainLayout",["Page" => "Page/Home"]);
    }


 }


?>