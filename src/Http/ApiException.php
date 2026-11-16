<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Exception yang langsung diterjemahkan menjadi response error JSON
 * (status code + pesan + detail error per-field bila ada).
 */
final class ApiException extends \RuntimeException
{
    /**
     * @param array<string,string> $errors  Pesan error per field (untuk 422)
     * @param array<string,string> $headers Header tambahan (mis. Allow, WWW-Authenticate)
     */
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $errors = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }
}
