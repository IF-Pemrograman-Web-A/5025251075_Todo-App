<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');

$data = getJsonInput();
$imageData = $data['image'] ?? '';

if (!is_string($imageData) ||
    !preg_match('/^data:image\/jpeg;base64,/', $imageData)) {
    jsonResponse([
        'success' => false,
        'message' => 'Format gambar tidak valid'
    ], 422);
}

$base64 = substr($imageData, strpos($imageData, ',') + 1);
$binary = base64_decode($base64, true);

if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
    jsonResponse([
        'success' => false,
        'message' => 'Gambar tidak valid atau melebihi 5 MB'
    ], 422);
}

$imageInfo = @getimagesizefromstring($binary);

if ($imageInfo === false || $imageInfo['mime'] !== 'image/jpeg') {
    jsonResponse([
        'success' => false,
        'message' => 'File harus berupa gambar'
    ], 422);
}

$uploadDir = dirname(__DIR__) . '/uploads';

if (!is_dir($uploadDir) &&
    !mkdir($uploadDir, 0755, true) &&
    !is_dir($uploadDir)) {
    jsonResponse([
        'success' => false,
        'message' => 'Folder upload tidak dapat dibuat'
    ], 500);
}

$fileName = bin2hex(random_bytes(16)) . '.jpg';
$filePath = $uploadDir . '/' . $fileName;

if (file_put_contents($filePath, $binary) === false) {
    jsonResponse([
        'success' => false,
        'message' => 'Gambar gagal disimpan'
    ], 500);
}

jsonResponse([
    'success' => true,
    'message' => 'Gambar berhasil diunggah',
    'data' => [
        'image_path' => 'uploads/' . $fileName
    ]
], 201);