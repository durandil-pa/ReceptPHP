<?php
declare(strict_types=1);

class User
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function findByUsername(string $username)
    {
        $this->db->query('SELECT * FROM users WHERE username = :username LIMIT 1');
        $this->db->bind(':username',$username);
        return $this->db->single();
    }
}
