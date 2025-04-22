<?php
class Home extends Controller
{
    public function Show()
    {
        // Redirect đến Question/List
        header("Location: /QAReviewer/Question/List");
        
        exit(); // Dừng thực thi sau khi redirect
    }
}
?>