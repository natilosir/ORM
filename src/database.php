<?php

namespace natilosir\orm;

use PDO;
use PDOException;

class database
{
    private $host; // Database host

    private $db_name; // Database name

    private $username; // Database username

    private $password; // Database password

    private $connection;

    public function __construct()
    {
        $config = require __DIR__.'/../../../../config.php';

        $this->host     = $config['database']['host'];
        $this->db_name  = $config['database']['database'];
        $this->username = $config['database']['user'];
        $this->password = $config['database']['password'];
    }

    public function getConnection()
    {
        $this->connection = null;

        try {
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4", $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo 'Connection error: '.$exception->getMessage();
        }

        return $this->connection;
    }
}
