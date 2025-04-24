<?php

 class Auth extends Controller
 {
    public function Login()
    {   
        if($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            $userModel = $this->model('UserModel', $username, $password);
            $user = $userModel->GetUser($userModel);          

            if($user)
            {

                $_SESSION['userID'] = $user[0]['UserID'] ?? null;
                $_SESSION['username'] = $username ?? null;
                $_SESSION['password'] = $password ?? null; 
                $_SESSION['role'] = $user[0]['Role'] ?? "Guest";
                header("Location: /QAReviewer/Home/");
            }
            else
            {
                echo "Invalid username or password.";
                $view = $this->view("Page/Login",[]);
            }
        }
        else
        {
            // Load the login view
            $this->view("Page/Login");
        }


    }
    function Logout()
    {
        session_start();
        unset($_SESSION['username']);
        unset($_SESSION['password']);
        session_destroy();
        header("Location: /QAReviewer/Home/");
        exit();

    }


 }


?>