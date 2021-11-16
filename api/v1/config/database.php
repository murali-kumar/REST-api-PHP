<?php
require_once ('data_helper.php');

class Database{	
	
	// specify your own database credentials
    private $host = "Server IP/name";
    private $db_name = "Database name";
    private $username = "Database username";
    private $password = "Database Password";
    public $conn;
	
	// get the database connection
    public function getConnection(){  
        $this->conn = null;  
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        }catch(PDOException $exception){
            echo generateResponseData(0, NULL, $exception->getCode(), $exception->getMessage());
			die();
        }  
        return $this->conn;
    }
}
?>