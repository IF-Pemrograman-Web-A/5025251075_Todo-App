<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

$data = getJsonInput();

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    jsonResponse([
        'success' => false,
        'message' => 'ID todo tidak valid'
    ], 422);
}

$allowedFields = [
    'title',
    'description',
    'due_date',
    'notification_time',
    'image_path',
    'is_completed',
    'visibility'
];

$updates = [];
$params = [
    'id' => $id,
    'owner_id' => 1
];

if (count(array_intersect(array_keys($data), $allowedFields)) === 0) {
    jsonResponse([
        'success' => false,
        'message' => 'Tidak ada data yang akan diubah'
    ], 422);
}

if (array_key_exists('title', $data)) {
    if (!is_string($data['title']) || trim($data['title']) === '') {
        jsonResponse([
            'success' => false,
            'message' => 'Judul todo wajib diisi'
        ], 422);
    }

    $title = trim($data['title']);

    if (mb_strlen($title) > 255) {
        jsonResponse([
            'success' => false,
            'message' => 'Judul maksimal 255 karakter'
        ], 422);
    }

    $updates[] = 'title = :title';
    $params['title'] = $title;
}

if (array_key_exists('description', $data)) {
    $updates[] = 'description = :description';
    $params['description'] = nullableString($data['description']);
}

if (array_key_exists('due_date', $data)) {
    $dueDate = $data['due_date'];

    if (
        !is_string($dueDate) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)
    ) {
        jsonResponse([
            'success' => false,
            'message' => 'Tanggal harus menggunakan format YYYY-MM-DD'
        ], 422);
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);

    if (!$date || $date->format('Y-m-d') !== $dueDate) {
        jsonResponse([
            'success' => false,
            'message' => 'Tanggal todo tidak valid'
        ], 422);
    }

    $updates[] = 'due_date = :due_date';
    $params['due_date'] = $dueDate;
}

if (array_key_exists('notification_time', $data)) {
    $notificationTime = nullableString($data['notification_time']);

    if (
        $notificationTime !== null &&
        !DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $notificationTime
        )
    ) {
        jsonResponse([
            'success' => false,
            'message' => 'Waktu pengingat harus menggunakan format YYYY-MM-DD HH:MM:SS'
        ], 422);
    }

    $updates[] = 'notification_time = :notification_time';
    $params['notification_time'] = $notificationTime;
}

if (array_key_exists('image_path', $data)) {
    $updates[] = 'image_path = :image_path';
    $params['image_path'] = nullableString($data['image_path']);
}

if (array_key_exists('is_completed', $data)) {
    $completed = filter_var(
        $data['is_completed'],
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    if ($completed === null) {
        jsonResponse([
            'success' => false,
            'message' => 'Status is_completed harus berupa boolean'
        ], 422);
    }

    $updates[] = 'is_completed = :is_completed';
    $params['is_completed'] = $completed ? 1 : 0;
}

if (array_key_exists('visibility', $data)) {
    if (!in_array($data['visibility'], ['personal', 'shared'], true)) {
        jsonResponse([
            'success' => false,
            'message' => 'Visibility harus personal atau shared'
        ], 422);
    }

    $updates[] = 'visibility = :visibility';
    $params['visibility'] = $data['visibility'];
}

try {
    $sql = "
        UPDATE todos
        SET " . implode(', ', $updates) . "
        WHERE id = :id AND owner_id = :owner_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        $check = $pdo->prepare(
            'SELECT id FROM todos WHERE id = :id AND owner_id = :owner_id'
        );

        $check->execute([
            'id' => $id,
            'owner_id' => 1
        ]);

        if (!$check->fetch()) {
            jsonResponse([
                'success' => false,
                'message' => 'Todo tidak ditemukan'
            ], 404);
        }
    }

    jsonResponse([
        'success' => true,
        'message' => 'Todo berhasil diperbarui'
    ]);
} catch (Throwable $e) {
    error_log('API update error: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'Gagal memperbarui todo'
    ], 500);
}