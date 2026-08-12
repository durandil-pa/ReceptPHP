<?php
declare(strict_types=1);

class Recipe
{
    private $db;
    public function __construct(){ $this->db=new Database(); }

    public function replaceIngredients(int $recipeId,array $amounts,array $units,array $names): void
    {
        $this->db->query("DELETE FROM recipe_ingredients WHERE recipe_id=:id");
        $this->db->bind(':id',$recipeId);
        $this->db->execute();

        foreach($names as $i=>$name){
            if(trim($name)===''){ continue; }

            $this->db->query("INSERT INTO recipe_ingredients
            (recipe_id,amount,unit_id,ingredient_name)
            VALUES(:recipe,:amount,:unit,:name)");
            $this->db->bind(':recipe',$recipeId);
            $this->db->bind(':amount',$amounts[$i]);
            $this->db->bind(':unit',$units[$i]);
            $this->db->bind(':name',$name);
            $this->db->execute();
        }
    }
}
