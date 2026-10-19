<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}


function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);

        jsonResponse([
            'success' => false,
            'message' => 'HTTP method tidak diizinkan.'
        ], 405);
    }
}


function getJsonInput(): array
{
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput ?: '', true);

    if (!is_array($data)) {
        jsonResponse([
            'success' => false,
            'message' => 'Body request harus berupa JSON yang valid.'
        ], 400);
    }

    return $data;
}


function nullableString(mixed $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    return is_string($value) ? trim($value) : null;
}