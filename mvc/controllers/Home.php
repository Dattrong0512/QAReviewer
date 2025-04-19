<?php

 class Home extends Controller
 {
    public function Show()
    {   
        session_start();
        if(isset($_SESSION['username']))
        {
            $view = $this->view("Layout/MainLayout",["Page" => "Page/Home"]);
        }
        else
        {
            $view = $this->view("Page/Login",[]);
        }

    }


 }


?>