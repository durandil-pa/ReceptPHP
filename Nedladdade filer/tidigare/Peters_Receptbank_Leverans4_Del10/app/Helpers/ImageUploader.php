<?php
declare(strict_types=1);

class ImageUploader
{
    public static function upload(array $file,string $targetDir='uploads/recipes/')
    {
        if($file['error']!==UPLOAD_ERR_OK){ return null; }

        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $mime=mime_content_type($file['tmp_name']);

        if(!isset($allowed[$mime])){
            throw new Exception('Ogiltig filtyp.');
        }

        if($file['size']>5*1024*1024){
            throw new Exception('Max 5 MB.');
        }

        $name=uniqid('recipe_',true).'.'.$allowed[$mime];
        $path=$targetDir.$name;

        if(!move_uploaded_file($file['tmp_name'],$path)){
            throw new Exception('Kunde inte spara bilden.');
        }

        return $path;
    }
}
