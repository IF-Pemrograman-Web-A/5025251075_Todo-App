<?php

declare(strict_types=1);

namespace App\Http;

/** Helper untuk mengirim response JSON dengan format yang konsisten. */
final class Response
{
    /** @param array<string,string> $headers */
    public static function json(array $body, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** 200 / 201 dengan data. */
    public static function success(mixed $data, int $status = 200, ?string $message = null, array $headers = []): never
    {
        $body = ['success' => true];

        if ($message !== null) {
            $body['message'] = $message;
        }

        $body['data'] = $data;

        self::json($body, $status, $headers);
    }

    /** 204 No Content: tidak boleh ada body. */
    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    /** @param array<string,string> $errors @param array<string,string> $headers */
    public static function error(int $status, string $message, array $errors = [], array $headers = []): never
    {
        $body = ['success' => false, 'message' => $message];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        self::json($body, $status, $headers);
    }
}
