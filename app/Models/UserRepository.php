<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Models;

use PDO;
use PDOException;

final class UserRepository
{
    private const TABLE = 'users';

    private bool $tableReady = false;

    public function __construct(
        private readonly PDO $connection,
    ) {}

    public function ensureTable(): void
    {
        if ($this->tableReady) {
            return;
        }

        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                email_verified_at TIMESTAMP NULL DEFAULT NULL,
                password VARCHAR(255) NOT NULL,
                remember_token VARCHAR(100) NULL,
                role ENUM(\'dev\', \'superadmin\', \'admin\', \'user\') NOT NULL DEFAULT \'user\',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY users_username_unique (username),
                UNIQUE KEY users_email_unique (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->tableReady = true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $this->ensureTable();

        $statement = $this->connection->prepare(
            'SELECT id, username, name, email, password, created_at, updated_at FROM ' . self::TABLE . ' WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $this->ensureTable();

        $statement = $this->connection->prepare(
            'SELECT id, username, name, email, password, created_at, updated_at FROM ' . self::TABLE . ' WHERE username = :username LIMIT 1'
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $this->ensureTable();

        $statement = $this->connection->prepare(
            'SELECT id, username, name, email, password, created_at, updated_at FROM ' . self::TABLE . ' WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $this->ensureTable();

        $statement = $this->connection->query(
            'SELECT id, username, name, email, created_at, updated_at FROM ' . self::TABLE . ' ORDER BY id DESC'
        );
        $users = $statement->fetchAll();

        return is_array($users) ? $users : [];
    }

    public function create(string $username, string $name, string $email, string $hashedPassword): ?int
    {
        $this->ensureTable();

        try {
            $statement = $this->connection->prepare(
                'INSERT INTO ' . self::TABLE . ' (username, name, email, password) VALUES (:username, :name, :email, :password)'
            );

            $statement->execute([
                'username' => $username,
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return null;
            }

            throw $exception;
        }

        return (int) $this->connection->lastInsertId();
    }

    public function emailExistsForAnotherUser(string $email, int $exceptId): bool
    {
        $this->ensureTable();

        $statement = $this->connection->prepare(
            'SELECT id FROM ' . self::TABLE . ' WHERE email = :email AND id <> :id LIMIT 1'
        );
        $statement->execute([
            'email' => $email,
            'id' => $exceptId,
        ]);

        return is_array($statement->fetch());
    }

    public function update(int $id, string $name, string $email, ?string $hashedPassword = null): bool
    {
        $this->ensureTable();

        if ($hashedPassword !== null) {
            $statement = $this->connection->prepare(
                'UPDATE ' . self::TABLE . ' SET name = :name, email = :email, password = :password WHERE id = :id'
            );

            return $statement->execute([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
            ]);
        }

        $statement = $this->connection->prepare(
            'UPDATE ' . self::TABLE . ' SET name = :name, email = :email WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);
    }

    public function delete(int $id): bool
    {
        $this->ensureTable();

        $statement = $this->connection->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = :id');
        return $statement->execute(['id' => $id]);
    }
}
