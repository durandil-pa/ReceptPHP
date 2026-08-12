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
     * @return array<int, array<string, mixed>>
     */
    public function categories(): array
    {
        return $this->connection
            ->query('SELECT id, name FROM categories ORDER BY name')
            ->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function units(): array
    {
        return $this->connection
            ->query('SELECT id, name, short_name FROM units ORDER BY id')
            ->fetchAll();
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

        if ($recipe === false) {
            return null;
        }

        $ingredients = $this->connection->prepare(
            'SELECT ingredient_name, amount, units.short_name AS unit
             FROM recipe_ingredients
             LEFT JOIN units ON units.id = recipe_ingredients.unit_id
             WHERE recipe_id = :recipe_id
             ORDER BY sort_order, id'
        );
        $ingredients->execute(['recipe_id' => $id]);
        $recipe['ingredients'] = $ingredients->fetchAll();

        return $recipe;
    }

    /**
     * @param array<string, mixed> $recipe
     * @param array<int, array<string, mixed>> $ingredients
     */
    public function create(array $recipe, array $ingredients, int $userId): int
    {
        return $this->databaseTransaction(function () use ($recipe, $ingredients, $userId): int {
            $statement = $this->connection->prepare(
                'INSERT INTO recipes
                 (category_id, created_by, title, description, servings, cook_time, instructions)
                 VALUES (:category_id, :created_by, :title, :description, :servings, :cook_time, :instructions)'
            );
            $statement->execute([
                'category_id' => $recipe['category_id'] ?: null,
                'created_by' => $userId,
                'title' => $recipe['title'],
                'description' => $recipe['description'] === '' ? null : $recipe['description'],
                'servings' => $recipe['servings'] ?: null,
                'cook_time' => $recipe['cook_time'] ?: null,
                'instructions' => $recipe['instructions'],
            ]);

            $recipeId = (int) $this->connection->lastInsertId();
            $ingredientStatement = $this->connection->prepare(
                'INSERT INTO recipe_ingredients
                 (recipe_id, ingredient_name, amount, unit_id, sort_order)
                 VALUES (:recipe_id, :ingredient_name, :amount, :unit_id, :sort_order)'
            );

            foreach ($ingredients as $position => $ingredient) {
                $ingredientStatement->execute([
                    'recipe_id' => $recipeId,
                    'ingredient_name' => $ingredient['name'],
                    'amount' => $ingredient['amount'],
                    'unit_id' => $ingredient['unit_id'],
                    'sort_order' => $position,
                ]);
            }

            return $recipeId;
        });
    }

    /**
     * @return mixed
     */
    private function databaseTransaction(callable $callback)
    {
        $this->connection->beginTransaction();

        try {
            $result = $callback();
            $this->connection->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }
}
