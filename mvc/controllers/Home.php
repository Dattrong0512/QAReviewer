<?php
class Home extends Controller
{
    public function Show()
    {
      
        // Redirect đến Question/List
        $this->view("Layout/MainLayout", [
            "Page" => "Page/Home"]);
        
        exit(); // Dừng thực thi sau khi redirect
    }
}
?>