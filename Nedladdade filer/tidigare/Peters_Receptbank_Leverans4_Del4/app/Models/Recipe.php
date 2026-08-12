<?php
declare(strict_types=1);

class Recipe
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function all(): array
    {
        $this->db->query("SELECT id, title, category_id, created_at FROM recipes ORDER BY title");
        return $this->db->resultSet();
    }

    public function find(int $id)
    {
        $this->db->query("SELECT * FROM recipes WHERE id=:id");
        $this->db->bind(':id',$id);
        return $this->db->single();
    }
}
