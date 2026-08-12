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

    /** @return array<int, array<string, mixed>> */
    public function search(string $query = '', int $categoryId = 0, int $userId = 0, bool $favoritesOnly = false): array
    {
        $sql = 'SELECT recipes.id, recipes.title, recipes.image_path, recipes.created_at, categories.name AS category_name,
                       recipe_favorites.recipe_id IS NOT NULL AS is_favorite
                FROM recipes
                LEFT JOIN categories ON categories.id = recipes.category_id
                LEFT JOIN recipe_favorites ON recipe_favorites.recipe_id = recipes.id
                    AND recipe_favorites.user_id = :favorite_user_id
                WHERE 1 = 1';
        $parameters = ['favorite_user_id' => $userId];
        if ($query !== '') {
            $sql .= ' AND (
                recipes.title LIKE :query_title
                OR recipes.description LIKE :query_description
                OR EXISTS (
                    SELECT 1 FROM recipe_ingredients
                    WHERE recipe_ingredients.recipe_id = recipes.id
                    AND recipe_ingredients.ingredient_name LIKE :query_ingredient
                )
            )';
            $parameters['query_title'] = '%' . $query . '%';
            $parameters['query_description'] = '%' . $query . '%';
            $parameters['query_ingredient'] = '%' . $query . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND recipes.category_id = :category_id';
            $parameters['category_id'] = $categoryId;
        }
        if ($favoritesOnly) {
            $sql .= ' AND recipe_favorites.recipe_id IS NOT NULL';
        }
        $statement = $this->connection->prepare($sql . ' ORDER BY recipes.created_at DESC, recipes.id DESC');
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

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT recipes.id, recipes.category_id, recipes.title, recipes.description, recipes.image_path, recipes.source_url,
                    recipes.servings, recipes.cook_time, recipes.instructions, recipes.created_at,
                    recipes.updated_at, categories.name AS category_name
             FROM recipes LEFT JOIN categories ON categories.id = recipes.category_id
             WHERE recipes.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $recipe = $statement->fetch();
        if ($recipe === false) {
            return null;
        }
        $ingredients = $this->connection->prepare(
            'SELECT ingredient_name, amount, unit_id, units.short_name AS unit
             FROM recipe_ingredients LEFT JOIN units ON units.id = recipe_ingredients.unit_id
             WHERE recipe_ingredients.recipe_id = :recipe_id
             ORDER BY recipe_ingredients.sort_order, recipe_ingredients.id'
        );
        $ingredients->execute(['recipe_id' => $id]);
        $recipe['ingredients'] = $ingredients->fetchAll();
        return $recipe;
    }

    /**
     * @param array<string, mixed> $recipe
     * @param array<int, array<string, mixed>> $ingredients
     */
    public function create(array $recipe, array $ingredients, int $userId, ?string $imagePath): int
    {
        return $this->databaseTransaction(function () use ($recipe, $ingredients, $userId, $imagePath): int {
            $statement = $this->connection->prepare(
                'INSERT INTO recipes (category_id, created_by, title, description, servings, cook_time, instructions, image_path, source_url)
                 VALUES (:category_id, :created_by, :title, :description, :servings, :cook_time, :instructions, :image_path, :source_url)'
            );
            $statement->execute($this->recipeParameters($recipe, ['created_by' => $userId, 'image_path' => $imagePath]));
            $recipeId = (int) $this->connection->lastInsertId();
            $this->replaceIngredients($recipeId, $ingredients);
            return $recipeId;
        });
    }

    /**
     * @param array<string, mixed> $recipe
     * @param array<int, array<string, mixed>> $ingredients
     */
    public function update(int $recipeId, array $recipe, array $ingredients, ?string $imagePath): void
    {
        $this->databaseTransaction(function () use ($recipeId, $recipe, $ingredients, $imagePath): void {
            $sql = 'UPDATE recipes SET category_id = :category_id, title = :title, description = :description,
                    servings = :servings, cook_time = :cook_time, instructions = :instructions, source_url = :source_url';
            $parameters = $this->recipeParameters($recipe, ['id' => $recipeId]);
            if ($imagePath !== null) {
                $sql .= ', image_path = :image_path';
                $parameters['image_path'] = $imagePath;
            }
            $statement = $this->connection->prepare($sql . ' WHERE id = :id');
            $statement->execute($parameters);
            $this->replaceIngredients($recipeId, $ingredients);
        });
    }

    public function delete(int $recipeId): ?string
    {
        $recipe = $this->find($recipeId);
        if ($recipe === null) {
            return null;
        }
        $statement = $this->connection->prepare('DELETE FROM recipes WHERE id = :id');
        $statement->execute(['id' => $recipeId]);
        return $recipe['image_path'];
    }

    /** @param array<int, array<string, mixed>> $ingredients */
    private function replaceIngredients(int $recipeId, array $ingredients): void
    {
        $delete = $this->connection->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id');
        $delete->execute(['recipe_id' => $recipeId]);
        $insert = $this->connection->prepare(
            'INSERT INTO recipe_ingredients (recipe_id, ingredient_name, amount, unit_id, sort_order)
             VALUES (:recipe_id, :ingredient_name, :amount, :unit_id, :sort_order)'
        );
        foreach ($ingredients as $position => $ingredient) {
            $insert->execute(['recipe_id' => $recipeId, 'ingredient_name' => $ingredient['name'],
                'amount' => $ingredient['amount'], 'unit_id' => $ingredient['unit_id'], 'sort_order' => $position]);
        }
    }

    /** @param array<string, mixed> $recipe @param array<string, mixed> $extra @return array<string, mixed> */
    private function recipeParameters(array $recipe, array $extra = []): array
    {
        return array_merge(['category_id' => $recipe['category_id'] ?: null, 'title' => $recipe['title'],
            'description' => $recipe['description'] === '' ? null : $recipe['description'],
            'servings' => $recipe['servings'] ?: null, 'cook_time' => $recipe['cook_time'] ?: null,
            'instructions' => $recipe['instructions'], 'source_url' => $recipe['source_url'] === '' ? null : $recipe['source_url']], $extra);
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
