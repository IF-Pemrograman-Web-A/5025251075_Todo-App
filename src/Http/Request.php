<?php

declare(strict_types=1);

namespace App\Http;

/** Pembungkus request HTTP: method, path, query, header, body JSON. */
final class Request
{
    /** @param array<string,string> $headers header dengan key lowercase */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly array $headers,
        private readonly string $rawBody,
    ) {
    }

    public static function capture(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Ambil bagian setelah ".../api" (dan abaikan "/index.php" bila ikut terpanggil)
        $path = '/';
        if (preg_match('#/api(?:/index\.php)?(/.*)?$#', $uri, $m)) {
            $path = $m[1] ?? '/';
        }
        $path = '/' . trim($path, '/');

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        // Beberapa konfigurasi Apache mengganti nama header Authorization
        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $headers,
            (string) file_get_contents('php://input'),
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');

        if ($auth !== null && preg_match('/^Bearer\s+(\S+)$/i', $auth, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Body JSON sebagai array asosiatif.
     * 415 bila Content-Type bukan JSON, 400 bila JSON rusak / bukan object.
     *
     * @return array<string,mixed>
     */
    public function json(): array
    {
        $type = strtolower((string) $this->header('content-type'));

        if (!str_contains($type, 'application/json')) {
            throw new ApiException(415, 'Content-Type harus application/json.');
        }

        $data = json_decode($this->rawBody, true);

        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new ApiException(400, 'Body request harus berupa JSON object yang valid.');
        }

        return $data;
    }
}
