<?php
declare(strict_types=1);

class Recipe
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function ingredients(int $recipeId): array
    {
        $this->db->query(
            "SELECT * FROM recipe_ingredients
             WHERE recipe_id=:id
             ORDER BY id"
        );
        $this->db->bind(':id',$recipeId);
        return $this->db->resultSet();
    }

    public function update(array $data): bool
    {
        $this->db->query(
            "UPDATE recipes
             SET title=:title,
                 category_id=:category,
                 servings=:servings,
                 cook_time=:cook_time,
                 instructions=:instructions
             WHERE id=:id"
        );

        $this->db->bind(':id',$data['id']);
        $this->db->bind(':title',$data['title']);
        $this->db->bind(':category',$data['category_id']);
        $this->db->bind(':servings',$data['servings']);
        $this->db->bind(':cook_time',$data['cook_time']);
        $this->db->bind(':instructions',$data['instructions']);

        return $this->db->execute();
    }
}
