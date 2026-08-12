<?php
declare(strict_types=1);

class RecipeController extends Controller
{
    private $recipe;
    public function __construct(){ $this->recipe=new Recipe(); }

    public function update(): void
    {
        if($_SERVER['REQUEST_METHOD']!=='POST'){
            $this->redirect('index.php?page=recipes');
        }

        $this->recipe->update($_POST);
        $this->recipe->replaceIngredients(
            (int)$_POST['id'],
            $_POST['amount'] ?? [],
            $_POST['unit_id'] ?? [],
            $_POST['ingredient_name'] ?? []
        );

        $this->redirect('index.php?page=recipe_edit&id='.(int)$_POST['id']);
    }
}
