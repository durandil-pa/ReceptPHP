<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

final class RecipeRepository
{
    /** @var PDO */
    private $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->connection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $statement = $this->connection->query(
            'SELECT recipes.id, recipes.title, recipes.description, recipes.servings,
                    recipes.cook_time, recipes.created_at, categories.name AS category_name
             FROM recipes
             LEFT JOIN categories ON categories.id = recipes.category_id
             ORDER BY recipes.created_at DESC, recipes.id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT recipes.id, recipes.title, recipes.description, recipes.servings,
                    recipes.cook_time, recipes.instructions, recipes.created_at,
                    recipes.updated_at, categories.name AS category_name
             FROM recipes
             LEFT JOIN categories ON categories.id = recipes.category_id
             WHERE recipes.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $recipe = $statement->fetch();

        return $recipe === false ? null : $recipe;
    }

    /**
     * @param array<string, mixed> $recipe
     */
    public function create(array $recipe, int $userId): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO recipes (created_by, title, description, servings, cook_time, instructions)
             VALUES (:created_by, :title, :description, :servings, :cook_time, :instructions)'
        );
        $statement->execute([
            'created_by' => $userId,
            'title' => $recipe['title'],
            'description' => $recipe['description'] === '' ? null : $recipe['description'],
            'servings' => $recipe['servings'] ?: null,
            'cook_time' => $recipe['cook_time'] ?: null,
            'instructions' => $recipe['instructions'],
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
