<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/** Semua query SQL untuk tabel todos (prepared statement). */
final class TodoRepository
{
    private const COLUMNS = 'id, owner_id, title, description, due_date, notification_time,
                             image_path, is_completed, visibility, created_at, updated_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @param array{visibility?:string,completed?:bool,q?:string} $filters
     * @return list<array<string,mixed>>
     */
    public function listByOwner(int $ownerId, array $filters = []): array
    {
        $where  = ['owner_id = :owner_id'];
        $params = ['owner_id' => $ownerId];

        if (isset($filters['visibility'])) {
            $where[] = 'visibility = :visibility';
            $params['visibility'] = $filters['visibility'];
        }

        if (isset($filters['completed'])) {
            $where[] = 'is_completed = :completed';
            $params['completed'] = $filters['completed'] ? 1 : 0;
        }

        if (isset($filters['q']) && $filters['q'] !== '') {
            // Placeholder tidak boleh dipakai dua kali (EMULATE_PREPARES = false)
            $where[] = '(title LIKE :q_title OR description LIKE :q_desc)';
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $params['q_title'] = $like;
            $params['q_desc']  = $like;
        }

        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM todos WHERE ' . implode(' AND ', $where)
            . ' ORDER BY created_at DESC, id DESC'
        );
        $stmt->execute($params);

        return array_map([self::class, 'cast'], $stmt->fetchAll());
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM todos WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row ? self::cast($row) : null;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private static function cast(array $row): array
    {
        $row['is_completed'] = (bool) $row['is_completed'];

        return $row;
    }

    /** @param array<string,mixed> $data field sudah tervalidasi */
    public function create(int $ownerId, array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO todos (owner_id, title, description, due_date, notification_time,
                                image_path, is_completed, visibility)
             VALUES (:owner_id, :title, :description, :due_date, :notification_time,
                     :image_path, :is_completed, :visibility)'
        );

        $stmt->execute([
            'owner_id'          => $ownerId,
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'due_date'          => $data['due_date'],
            'notification_time' => $data['notification_time'] ?? null,
            'image_path'        => $data['image_path'] ?? null,
            'is_completed'      => !empty($data['is_completed']) ? 1 : 0,
            'visibility'        => $data['visibility'] ?? 'personal',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update hanya kolom yang ada di $data (nama kolom berasal dari whitelist
     * validator, bukan dari input user).
     *
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): void
    {
        if ($data === []) {
            return;
        }

        $sets   = [];
        $params = ['id' => $id];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = :{$column}";
            $params[$column] = is_bool($value) ? (int) $value : $value;
        }

        $stmt = $this->pdo->prepare('UPDATE todos SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM todos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
