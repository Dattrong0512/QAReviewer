<?php
class UserModel extends DB
{
    public $id; // Thêm thuộc tính id
    public $username;
    public $password;

    function __construct($username, $password)
    {
        parent::__construct(); // Gọi hàm khởi tạo của lớp cha (DB)
        $this->username = $username;
        $this->password = $password;
    }

    public function GetUser()
    {
        $query = $this->conn->prepare("SELECT * FROM users WHERE UserName = ? AND password = ?");
        $query->bind_param("ss", $this->username, $this->password);
        $query->execute();
        $results = $query->get_result();
        return $results->fetch_all(MYSQLI_ASSOC);
    }
}
