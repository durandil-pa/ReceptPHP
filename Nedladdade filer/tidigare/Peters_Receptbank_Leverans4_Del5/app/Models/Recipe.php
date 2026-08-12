<?php
declare(strict_types=1);

class Recipe
{
    private $db;
    public function __construct(){ $this->db=new Database(); }

    public function categories(): array
    {
        $this->db->query("SELECT id,name FROM categories ORDER BY name");
        return $this->db->resultSet();
    }

    public function create(array $d): bool
    {
        $this->db->query("INSERT INTO recipes
        (title,category_id,servings,cook_time,instructions)
        VALUES(:title,:category,:servings,:cook_time,:instructions)");
        $this->db->bind(':title',$d['title']);
        $this->db->bind(':category',$d['category_id']);
        $this->db->bind(':servings',$d['servings']);
        $this->db->bind(':cook_time',$d['cook_time']);
        $this->db->bind(':instructions',$d['instructions']);
        return $this->db->execute();
    }
}
