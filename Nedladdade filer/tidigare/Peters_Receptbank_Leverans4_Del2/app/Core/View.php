<?php
declare(strict_types=1);

class View
{
    public static function render(string $view, array $data=[]): void
    {
        extract($data);
        $file = APP_PATH . '/Views/' . $view . '.php';
        if(!file_exists($file)){
            throw new Exception('View saknas: '.$view);
        }
        require $file;
    }
}
