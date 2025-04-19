<?php

use Dom\Mysql;

class DB
{
    public $conn;
    private $servername = "localhost";
    private $username = "root";
    private $password = "root";
    private $dbname = "QAReviewerDB";

    function __construct()
    {
        $this->conn = mysqli_connect($this->servername, $this->username, $this->password);
        mysqli_select_db($this->conn, $this->dbname);
        mysqli_query($this->conn, "SET NAMES 'utf8mb4'");
    }
}
