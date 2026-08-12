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
    public function search(string $query = '', int $categoryId = 0): array
    {
        $sql = 'SELECT recipes.id, recipes.title, recipes.created_at, categories.name AS category_name
                FROM recipes
                LEFT JOIN categories ON categories.id = recipes.category_id
                WHERE 1 = 1';
        $parameters = [];

        if ($query !== '') {
            $sql .= ' AND (recipes.title LIKE :query OR recipes.description LIKE :query)';
            $parameters['query'] = '%' . $query . '%';
        }

        if ($categoryId > 0) {
            $sql .= ' AND recipes.category_id = :category_id';
            $parameters['category_id'] = $categoryId;
        }

        $sql .= ' ORDER BY recipes.created_at DESC, recipes.id DESC';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function categories(): array
    {
        return $this->connection->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function units(): array
    {
        return $this->connection->query('SELECT id, name, short_name FROM units ORDER BY id')->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT recipes.id, recipes.category_id, recipes.title, recipes.description,
                    recipes.servings, recipes.cook_time, recipes.instructions,
                    recipes.created_at, recipes.updated_at, categories.name AS category_name
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
            'SELECT ingredient_name, amount, unit_id, units.short_name AS unit
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
            $statement->execute($this->recipeParameters($recipe, ['created_by' => $userId]));

            $recipeId = (int) $this->connection->lastInsertId();
            $this->replaceIngredients($recipeId, $ingredients);

            return $recipeId;
        });
    }

    /**
     * @param array<string, mixed> $recipe
     * @param array<int, array<string, mixed>> $ingredients
     */
    public function update(int $recipeId, array $recipe, array $ingredients): void
    {
        $this->databaseTransaction(function () use ($recipeId, $recipe, $ingredients): void {
            $statement = $this->connection->prepare(
                'UPDATE recipes
                 SET category_id = :category_id, title = :title, description = :description,
                     servings = :servings, cook_time = :cook_time, instructions = :instructions
                 WHERE id = :id'
            );
            $statement->execute($this->recipeParameters($recipe, ['id' => $recipeId]));
            $this->replaceIngredients($recipeId, $ingredients);
        });
    }

    public function delete(int $recipeId): bool
    {
        $statement = $this->connection->prepare('DELETE FROM recipes WHERE id = :id');
        $statement->execute(['id' => $recipeId]);

        return $statement->rowCount() === 1;
    }

    /**
     * @param array<int, array<string, mixed>> $ingredients
     */
    private function replaceIngredients(int $recipeId, array $ingredients): void
    {
        $delete = $this->connection->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id');
        $delete->execute(['recipe_id' => $recipeId]);

        $insert = $this->connection->prepare(
            'INSERT INTO recipe_ingredients
             (recipe_id, ingredient_name, amount, unit_id, sort_order)
             VALUES (:recipe_id, :ingredient_name, :amount, :unit_id, :sort_order)'
        );

        foreach ($ingredients as $position => $ingredient) {
            $insert->execute([
                'recipe_id' => $recipeId,
                'ingredient_name' => $ingredient['name'],
                'amount' => $ingredient['amount'],
                'unit_id' => $ingredient['unit_id'],
                'sort_order' => $position,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $recipe
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function recipeParameters(array $recipe, array $extra = []): array
    {
        return array_merge([
            'category_id' => $recipe['category_id'] ?: null,
            'title' => $recipe['title'],
            'description' => $recipe['description'] === '' ? null : $recipe['description'],
            'servings' => $recipe['servings'] ?: null,
            'cook_time' => $recipe['cook_time'] ?: null,
            'instructions' => $recipe['instructions'],
        ], $extra);
    }

    /** @return mixed */
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
