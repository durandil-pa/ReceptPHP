<?php
declare(strict_types=1);

class RecipeController extends Controller
{
    private $recipe;

    public function __construct()
    {
        $this->recipe = new Recipe();
    }

    public function search(): void
    {
        $text = trim($_GET['q'] ?? '');
        $category = (int)($_GET['category'] ?? 0);

        $recipes = $this->recipe->search($text, $category);
        $categories = $this->recipe->categories();

        $this->view('recipes/search', [
            'recipes'=>$recipes,
            'categories'=>$categories,
            'q'=>$text,
            'selectedCategory'=>$category
        ]);
    }
}
