<?php
declare(strict_types=1);

class RecipeController extends Controller
{
    private $recipe;
    public function __construct(){ $this->recipe=new Recipe(); }

    public function create(): void
    {
        $categories = $this->recipe->categories();
        $this->view('recipes/create',['categories'=>$categories]);
    }

    public function store(): void
    {
        if($_SERVER['REQUEST_METHOD']!=='POST'){
            $this->redirect('index.php?page=recipes');
        }

        $data=[
            'title'=>trim($_POST['title'] ?? ''),
            'category_id'=>(int)($_POST['category_id'] ?? 0),
            'servings'=>(int)($_POST['servings'] ?? 0),
            'cook_time'=>(int)($_POST['cook_time'] ?? 0),
            'instructions'=>trim($_POST['instructions'] ?? '')
        ];

        $this->recipe->create($data);
        $this->redirect('index.php?page=recipes');
    }
}
