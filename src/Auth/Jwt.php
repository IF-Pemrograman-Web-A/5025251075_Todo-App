<?php

declare(strict_types=1);

namespace App\Auth;

use App\Http\ApiException;

/** JWT HS256 minimal (header.payload.signature, base64url). */
final class Jwt
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 3600,
    ) {
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET harus diisi minimal 32 karakter di config/.env');
        }
    }

    public static function fromEnv(): self
    {
        return new self(
            (string) ($_ENV['JWT_SECRET'] ?? ''),
            (int) ($_ENV['JWT_TTL'] ?? 3600),
        );
    }

    public function ttl(): int
    {
        return $this->ttl;
    }

    public function issue(int $userId): string
    {
        $now = time();

        $header  = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::b64(json_encode([
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ]));

        return "{$header}.{$payload}." . self::b64($this->sign("{$header}.{$payload}"));
    }

    /** @return int id user (claim "sub"), atau 401 bila token tidak valid/kedaluwarsa */
    public function verify(string $token): int
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw self::unauthorized('Token tidak valid.');
        }

        [$header, $payload, $signature] = $parts;

        $head = json_decode((string) self::unb64($header), true);

        // Tolak token dengan alg selain HS256 (mencegah serangan alg=none)
        if (!is_array($head) || ($head['alg'] ?? null) !== 'HS256') {
            throw self::unauthorized('Token tidak valid.');
        }

        $expected = $this->sign("{$header}.{$payload}");

        if (!hash_equals($expected, (string) self::unb64($signature))) {
            throw self::unauthorized('Token tidak valid.');
        }

        $claims = json_decode((string) self::unb64($payload), true);

        if (!is_array($claims) || !isset($claims['sub'], $claims['exp'])) {
            throw self::unauthorized('Token tidak valid.');
        }

        if ((int) $claims['exp'] < time()) {
            throw self::unauthorized('Token sudah kedaluwarsa. Silakan login kembali.');
        }

        return (int) $claims['sub'];
    }

    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->secret, true);
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function unb64(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }

    private static function unauthorized(string $message): ApiException
    {
        return new ApiException(401, $message, headers: ['WWW-Authenticate' => 'Bearer']);
    }
}
