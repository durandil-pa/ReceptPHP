<?php
declare(strict_types=1);

class Ingredient
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function units(): array
    {
        $this->db->query("SELECT id,name,short_name FROM units ORDER BY name");
        return $this->db->resultSet();
    }

    public function saveRecipeIngredient(int $recipeId, array $item): bool
    {
        $this->db->query(
            "INSERT INTO recipe_ingredients
            (recipe_id, ingredient_name, amount, unit_id)
            VALUES (:recipe_id,:ingredient,:amount,:unit)"
        );

        $this->db->bind(':recipe_id',$recipeId);
        $this->db->bind(':ingredient',$item['ingredient_name']);
        $this->db->bind(':amount',$item['amount']);
        $this->db->bind(':unit',$item['unit_id']);

        return $this->db->execute();
    }
}
