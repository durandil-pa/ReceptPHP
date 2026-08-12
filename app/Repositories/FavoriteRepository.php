<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;
use RuntimeException;

final class FavoriteRepository
{
    /** @var PDO */
    private $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->connection();
    }

    public function exists(int $userId, int $recipeId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM recipe_favorites WHERE user_id = :user_id AND recipe_id = :recipe_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
        return $statement->fetchColumn() !== false;
    }

    public function toggle(int $userId, int $recipeId): bool
    {
        $recipe = $this->connection->prepare('SELECT 1 FROM recipes WHERE id = :id LIMIT 1');
        $recipe->execute(['id' => $recipeId]);
        if ($recipe->fetchColumn() === false) {
            throw new RuntimeException('Recipe not found.');
        }

        $delete = $this->connection->prepare(
            'DELETE FROM recipe_favorites WHERE user_id = :user_id AND recipe_id = :recipe_id'
        );
        $delete->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
        if ($delete->rowCount() > 0) {
            return false;
        }

        $insert = $this->connection->prepare(
            'INSERT INTO recipe_favorites (user_id, recipe_id) VALUES (:user_id, :recipe_id)'
        );
        $insert->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
        return true;
    }
}
