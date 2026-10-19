<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

$data = getJsonInput();

$title = trim((string) ($data['title'] ?? ''));
$description = nullableString($data['description'] ?? null);
$dueDate = $data['due_date'] ?? null;
$notificationTime = nullableString($data['notification_time'] ?? null);
$imagePath = nullableString($data['image_path'] ?? null);
$visibility = $data['visibility'] ?? 'personal';

if ($title === '') {
    jsonResponse([
        'success' => false,
        'message' => 'Judul todo wajib diisi.'
    ], 422);
}

if (mb_strlen($title) > 255) {
    jsonResponse([
        'success' => false,
        'message' => 'Judul maksimal 255 karakter.'
    ], 422);
}

if (
    !is_string($dueDate) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)
) {
    jsonResponse([
        'success' => false,
        'message' => 'Tanggal todo harus menggunakan format YYYY-MM-DD'
    ], 422);
}

$date = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);

if (!$date || $date->format('Y-m-d') !== $dueDate) {
    jsonResponse([
        'success' => false,
        'message' => 'Tanggal todo tidak valid.'
    ], 422);
}

if (!in_array($visibility, ['personal', 'shared'], true)) {
    jsonResponse([
        'success' => false,
        'message' => 'Visibility harus personal atau shared'
    ], 422);
}

try {
    // Pengguna demo sementara.
    $ownerId = 1;

    $sql = "
        INSERT INTO todos (
            owner_id,
            title,
            description,
            due_date,
            notification_time,
            image_path,
            is_completed,
            visibility
        ) VALUES (
            :owner_id,
            :title,
            :description,
            :due_date,
            :notification_time,
            :image_path,
            0,
            :visibility
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'owner_id' => $ownerId,
        'title' => $title,
        'description' => $description,
        'due_date' => $dueDate,
        'notification_time' => $notificationTime,
        'image_path' => $imagePath,
        'visibility' => $visibility
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Todo berhasil ditambahkan',
        'data' => [
            'id' => (int) $pdo->lastInsertId()
        ]
    ], 201);
} catch (Throwable $e) {
    error_log('API add error: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'Gagal menambahkan todo'
    ], 500);
}