<?php
declare(strict_types=1);

class RecipeImageController extends Controller
{
    private $recipe;

    public function __construct()
    {
        $this->recipe=new Recipe();
    }

    public function upload(): void
    {
        $id=(int)($_POST['recipe_id'] ?? 0);

        try{
            $path=ImageUploader::upload($_FILES['recipe_image']);
            if($path){
                $this->recipe->updateImage($id,$path);
            }
            $_SESSION['success']='Receptbild sparad.';
        }catch(Exception $e){
            $_SESSION['error']=$e->getMessage();
        }

        $this->redirect('index.php?page=recipe_edit&id='.$id);
    }
}
