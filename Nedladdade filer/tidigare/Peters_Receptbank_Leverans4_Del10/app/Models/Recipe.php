<?php
declare(strict_types=1);

class Recipe
{
    private $db;
    public function __construct(){ $this->db=new Database(); }

    public function updateImage(int $recipeId,string $path): bool
    {
        $this->db->query("UPDATE recipes SET image_path=:img WHERE id=:id");
        $this->db->bind(':img',$path);
        $this->db->bind(':id',$recipeId);
        return $this->db->execute();
    }
}
