<?php
declare(strict_types=1);

class RecipeController extends Controller
{
    private $recipe;

    public function __construct()
    {
        $this->recipe = new Recipe();
    }

    public function index(): void
    {
        $recipes = $this->recipe->all();
        $this->view('recipes/index', ['recipes'=>$recipes]);
    }
}
