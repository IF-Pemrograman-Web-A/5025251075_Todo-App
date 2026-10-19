<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('GET');

try {
    // Sementara menggunakan user ID 1 untuk pengujian lokal.
    // Nanti diganti dengan ID pengguna dari sistem autentikasi.
    $ownerId = 1;

    $sql = "
        SELECT
            id,
            owner_id,
            title,
            description,
            due_date,
            notification_time,
            image_path,
            is_completed,
            visibility,
            created_at,
            updated_at
        FROM todos
        WHERE owner_id = :owner_id
        ORDER BY created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'owner_id' => $ownerId
    ]);

    $todos = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $todos
    ]);
} catch (Throwable $e) {
    error_log('API list error: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'Gagal mengambil daftar todo.'
    ], 500);
}