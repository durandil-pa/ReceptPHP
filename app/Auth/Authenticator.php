<?php
declare(strict_types=1);

namespace App\Auth;

use App\Database\Database;
use PDO;

final class Authenticator
{
    /** @var PDO */
    private $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->connection();
    }

    public function attempt(string $username, string $password): bool
    {
        $statement = $this->connection->prepare(
            'SELECT id, name, username, password_hash, role
             FROM users
             WHERE username = :username AND is_approved = 1
             LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'role' => $user['role'],
        ];

        return true;
    }

    public function check(): bool
    {
        return isset($_SESSION['user']['id']) && is_int($_SESSION['user']['id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return $this->check() ? $_SESSION['user'] : null;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}
