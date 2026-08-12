<?php
declare(strict_types=1);

class RecipeController extends Controller
{
    private $recipe;

    public function __construct()
    {
        $this->recipe = new Recipe();
    }

    public function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        $recipe = $this->recipe->find($id);
        $ingredients = $this->recipe->ingredients($id);
        $categories = $this->recipe->categories();

        $this->view('recipes/edit',[
            'recipe'=>$recipe,
            'ingredients'=>$ingredients,
            'categories'=>$categories
        ]);
    }
}
