<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** @return array<string,mixed>|null termasuk password_hash */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    public function nameOrEmailExists(string $name, string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE name = :name OR email = :email LIMIT 1');
        $stmt->execute(['name' => $name, 'email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)'
        );
        $stmt->execute(['name' => $name, 'email' => $email, 'hash' => $passwordHash]);

        return (int) $this->pdo->lastInsertId();
    }
}
