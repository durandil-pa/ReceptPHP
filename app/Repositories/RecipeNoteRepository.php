<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;
use RuntimeException;

final class RecipeNoteRepository
{
    /** @var PDO */
    private $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->connection();
    }

    public function find(int $userId, int $recipeId): ?string
    {
        $statement = $this->connection->prepare(
            'SELECT note FROM recipe_notes WHERE user_id = :user_id AND recipe_id = :recipe_id LIMIT 1'
        );
        $statement->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
        $note = $statement->fetchColumn();

        return $note === false ? null : (string) $note;
    }

    public function save(int $userId, int $recipeId, string $note): void
    {
        $recipe = $this->connection->prepare('SELECT 1 FROM recipes WHERE id = :id LIMIT 1');
        $recipe->execute(['id' => $recipeId]);
        if ($recipe->fetchColumn() === false) {
            throw new RuntimeException('Recipe not found.');
        }

        if ($note === '') {
            $delete = $this->connection->prepare(
                'DELETE FROM recipe_notes WHERE user_id = :user_id AND recipe_id = :recipe_id'
            );
            $delete->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
            return;
        }

        $statement = $this->connection->prepare(
            'INSERT INTO recipe_notes (user_id, recipe_id, note)
             VALUES (:user_id, :recipe_id, :note)
             ON DUPLICATE KEY UPDATE note = VALUES(note), updated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute(['user_id' => $userId, 'recipe_id' => $recipeId, 'note' => $note]);
    }
}
