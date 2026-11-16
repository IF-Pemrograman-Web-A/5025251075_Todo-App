<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Jwt;
use App\Http\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Jwt $jwt,
    ) {
    }

    /** POST /api/auth/register -> 201 | 409 | 422 */
    public function register(Request $request): never
    {
        $in = $request->json();

        $name     = is_string($in['name'] ?? null) ? trim($in['name']) : '';
        $email    = is_string($in['email'] ?? null) ? strtolower(trim($in['email'])) : '';
        $password = is_string($in['password'] ?? null) ? $in['password'] : '';

        $errors = [];

        if ($name === '' || mb_strlen($name) > 50) {
            $errors['name'] = 'Nama wajib diisi (maksimal 50 karakter).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors['email'] = 'Email tidak valid.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        }
        if ($errors !== []) {
            throw new ApiException(422, 'Data yang dikirim tidak valid.', $errors);
        }

        if ($this->users->nameOrEmailExists($name, $email)) {
            throw new ApiException(409, 'Nama atau email sudah terdaftar.');
        }

        $id = $this->users->create($name, $email, password_hash($password, PASSWORD_DEFAULT));

        Response::success(
            $this->users->findById($id),
            201,
            'Registrasi berhasil.',
            [],
        );
    }

    /** POST /api/auth/login -> 200 + token | 401 | 422 */
    public function login(Request $request): never
    {
        $in = $request->json();

        $email    = is_string($in['email'] ?? null) ? strtolower(trim($in['email'])) : '';
        $password = is_string($in['password'] ?? null) ? $in['password'] : '';

        $errors = array_filter([
            'email'    => $email === '' ? 'Email wajib diisi.' : '',
            'password' => $password === '' ? 'Password wajib diisi.' : '',
        ]);

        if ($errors !== []) {
            throw new ApiException(422, 'Data yang dikirim tidak valid.', $errors);
        }

        $user = $this->users->findByEmail($email);

        // Pesan sengaja sama untuk email salah / password salah (tidak membocorkan akun)
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new ApiException(
                401,
                'Email atau password salah.',
                headers: ['WWW-Authenticate' => 'Bearer'],
            );
        }

        Response::success([
            'token'      => $this->jwt->issue((int) $user['id']),
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->ttl(),
            'user'       => ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']],
        ], 200, 'Login berhasil.');
    }

    /** GET /api/auth/me -> 200 | 401 */
    public function me(Request $request, array $params, array $user): never
    {
        Response::success($user);
    }
}
