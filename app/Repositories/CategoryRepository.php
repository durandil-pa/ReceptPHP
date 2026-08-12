<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;
use RuntimeException;

final class CategoryRepository
{
    /** @var PDO */
    private $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->connection();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->connection->query(
            'SELECT categories.id, categories.name, COUNT(recipes.id) AS recipe_count
             FROM categories
             LEFT JOIN recipes ON recipes.category_id = categories.id
             GROUP BY categories.id, categories.name
             ORDER BY categories.name'
        )->fetchAll();
    }

    public function create(string $name): void
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 100) {
            throw new RuntimeException('Kategorinamnet måste innehålla mellan 1 och 100 tecken.');
        }

        $statement = $this->connection->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $name]);
        if ($statement->fetch() !== false) {
            throw new RuntimeException('Den kategorin finns redan.');
        }

        $slug = $this->uniqueSlug($this->slugify($name));
        $insert = $this->connection->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
        $insert->execute(['name' => $name, 'slug' => $slug]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM categories WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    private function slugify(string $value): string
    {
        $value = strtr($value, ['å' => 'a', 'ä' => 'a', 'ö' => 'o', 'Å' => 'a', 'Ä' => 'a', 'Ö' => 'o']);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');

        return $value === '' ? 'kategori' : $value;
    }

    private function uniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 2;
        $exists = $this->connection->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');

        do {
            $exists->execute(['slug' => $slug]);
            if ($exists->fetch() === false) {
                return $slug;
            }
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        } while ($suffix < 1000);

        throw new RuntimeException('Kunde inte skapa en unik kategoriadress.');
    }
}
