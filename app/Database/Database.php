<?php
declare(strict_types=1);

namespace App\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    /** @var PDO */
    private $connection;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->connection = $this->connect($config);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        if (!isset($config['database']) || !is_array($config['database'])) {
            throw new InvalidArgumentException('Database configuration is missing.');
        }

        return new self($config['database']);
    }

    public function connection(): PDO
    {
        return $this->connection;
    }

    /**
     * Run a callback in a transaction and roll it back if the callback fails.
     *
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        $this->connection->beginTransaction();

        try {
            $result = $callback($this->connection);
            $this->connection->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function connect(array $config): PDO
    {
        $driver = isset($config['driver']) ? (string) $config['driver'] : 'mysql';
        if ($driver !== 'mysql') {
            throw new InvalidArgumentException('Only the mysql PDO driver is supported.');
        }

        $host = $this->requiredString($config, 'host');
        $database = $this->requiredString($config, 'database');
        $charset = isset($config['charset']) ? (string) $config['charset'] : 'utf8mb4';
        $port = isset($config['port']) ? (int) $config['port'] : 3306;

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Database port must be between 1 and 65535.');
        }

        $options = isset($config['options']) && is_array($config['options'])
            ? $config['options']
            : [];

        $options += [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
        );

        try {
            return new PDO(
                $dsn,
                isset($config['username']) ? (string) $config['username'] : '',
                isset($config['password']) ? (string) $config['password'] : '',
                $options
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to the database.', 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function requiredString(array $config, string $key): string
    {
        if (!isset($config[$key]) || trim((string) $config[$key]) === '') {
            throw new InvalidArgumentException(sprintf('Database setting "%s" is required.', $key));
        }

        return (string) $config[$key];
    }
}
