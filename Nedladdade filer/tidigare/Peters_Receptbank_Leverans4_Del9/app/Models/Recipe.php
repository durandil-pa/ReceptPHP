<?php
declare(strict_types=1);

class Recipe
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function search(string $text='', int $category=0): array
    {
        $sql = "SELECT * FROM recipes WHERE 1=1";
        if($text !== ''){
            $sql .= " AND title LIKE :title";
        }
        if($category > 0){
            $sql .= " AND category_id=:category";
        }
        $sql .= " ORDER BY title";

        $this->db->query($sql);

        if($text !== ''){
            $this->db->bind(':title', '%'.$text.'%');
        }
        if($category > 0){
            $this->db->bind(':category', $category);
        }

        return $this->db->resultSet();
    }
}
