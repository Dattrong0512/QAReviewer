<?php
use Dom\Mysql;

require_once 'config.inc.php';

class DB
{
    public $conn;

    function __construct()
    {
        $this->conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$this->conn) {
            die("Connection failed: " . mysqli_connect_error());
        }

        mysqli_query($this->conn, "SET NAMES '" . DB_CHARSET . "'");
    }
}
?>