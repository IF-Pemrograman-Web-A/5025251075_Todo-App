<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

$data = getJsonInput();

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    jsonResponse([
        'success' => false,
        'message' => 'ID todo tidak valid.'
    ], 422);
}

try {
    $ownerId = 1;

    $stmt = $pdo->prepare(
        'DELETE FROM todos WHERE id = :id AND owner_id = :owner_id'
    );

    $stmt->execute([
        'id' => $id,
        'owner_id' => $ownerId
    ]);

    if ($stmt->rowCount() === 0) {
        jsonResponse([
            'success' => false,
            'message' => 'Todo tidak ditemukan.'
        ], 404);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Todo berhasil dihapus.'
    ]);
} catch (Throwable $e) {
    error_log('API delete error: ' . $e->getMessage());

    jsonResponse([
        'success' => false,
        'message' => 'Gagal menghapus todo.'
    ], 500);
}