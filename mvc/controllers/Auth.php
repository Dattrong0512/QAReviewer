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
                session_start();
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $password;
                $_SESSION['role'] = $user[0]['Role'];
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