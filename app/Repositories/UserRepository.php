<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;
use RuntimeException;

final class UserRepository
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
            'SELECT id, name, username, role, is_approved, created_at
             FROM users
             ORDER BY is_approved ASC, name, username'
        )->fetchAll();
    }

    public function create(string $name, string $username, string $password, string $role): void
    {
        $name = trim($name);
        $username = trim($username);
        $this->assertAccountInput($name, $username, $password, $role);

        $exists = $this->connection->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
        $exists->execute(['username' => $username]);
        if ($exists->fetchColumn() !== false) {
            throw new RuntimeException('Användarnamnet används redan.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO users (name, username, password_hash, role, is_approved)
             VALUES (:name, :username, :password_hash, :role, 1)'
        );
        $statement->execute([
            'name' => $name,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
    }



    public function register(string $name, string $username, string $password): void
    {
        $name = trim($name);
        $username = trim($username);
        $this->assertAccountInput($name, $username, $password, 'user');

        $exists = $this->connection->prepare('SELECT 1 FROM users WHERE username = :username LIMIT 1');
        $exists->execute(['username' => $username]);
        if ($exists->fetchColumn() !== false) {
            throw new RuntimeException('Användarnamnet används redan.');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO users (name, username, password_hash, role, is_approved)
             VALUES (:name, :username, :password_hash, :role, 0)'
        );
        $statement->execute([
            'name' => $name,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
        ]);
    }

    public function approve(int $userId): void
    {
        $statement = $this->connection->prepare(
            'UPDATE users SET is_approved = 1 WHERE id = :id AND is_approved = 0'
        );
        $statement->execute(['id' => $userId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Användaren kunde inte hittas eller är redan godkänd.');
        }
    }

    public function changeOwnPassword(int $userId, string $currentPassword, string $newPassword): void
    {
        if (strlen($newPassword) < 12) {
            throw new RuntimeException('Det nya lösenordet måste ha minst 12 tecken.');
        }

        $statement = $this->connection->prepare(
            'SELECT password_hash FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $userId]);
        $passwordHash = $statement->fetchColumn();

        if ($passwordHash === false) {
            throw new RuntimeException('Användaren kunde inte hittas.');
        }
        if (!password_verify($currentPassword, (string) $passwordHash)) {
            throw new RuntimeException('Nuvarande lösenord är fel.');
        }
        if (password_verify($newPassword, (string) $passwordHash)) {
            throw new RuntimeException('Välj ett nytt lösenord som skiljer sig från det nuvarande.');
        }

        $update = $this->connection->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'id' => $userId,
        ]);
    }

    public function changeRole(int $currentUserId, int $userId, string $role): void
    {
        if (!in_array($role, ['admin', 'user'], true)) {
            throw new RuntimeException('Den valda behörigheten är ogiltig.');
        }
        if ($userId === $currentUserId) {
            throw new RuntimeException('Du kan inte ändra din egen behörighet här.');
        }

        $statement = $this->connection->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $currentRole = $statement->fetchColumn();
        if ($currentRole === false) {
            throw new RuntimeException('Användaren kunde inte hittas.');
        }
        if ($currentRole === $role) {
            return;
        }
        if ($currentRole === 'admin' && $role === 'user' && $this->adminCount() <= 1) {
            throw new RuntimeException('Den sista administratören kan inte göras om till vanlig användare.');
        }

        $update = $this->connection->prepare('UPDATE users SET role = :role WHERE id = :id');
        $update->execute(['role' => $role, 'id' => $userId]);
    }

    private function adminCount(): int
    {
        return (int) $this->connection->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    private function assertAccountInput(string $name, string $username, string $password, string $role): void
    {
        if ($name === '' || strlen($name) > 100) {
            throw new RuntimeException('Namnet måste innehålla mellan 1 och 100 tecken.');
        }
        if (!preg_match('/^[A-Za-z0-9_.-]{3,100}$/', $username)) {
            throw new RuntimeException('Användarnamnet måste ha minst 3 tecken och får bara innehålla bokstäver, siffror, punkt, bindestreck och understreck.');
        }
        if (strlen($password) < 12) {
            throw new RuntimeException('Lösenordet måste ha minst 12 tecken.');
        }
        if (!in_array($role, ['admin', 'user'], true)) {
            throw new RuntimeException('Den valda behörigheten är ogiltig.');
        }
    }
}
