<?php

/**
 * Front controller REST API.
 *
 *   Request -> .htaccess -> index.php -> Router -> (Auth JWT) -> Controller
 *           -> Repository (PDO) -> Response JSON
 */

declare(strict_types=1);

use App\Auth\Jwt;
use App\Controllers\AuthController;
use App\Controllers\TodoController;
use App\Controllers\UploadController;
use App\Http\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Repositories\TodoRepository;
use App\Repositories\UserRepository;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Error PHP jangan bocor ke client; cukup masuk log
ini_set('display_errors', '0');

require_once __DIR__ . '/../../config/database.php'; // menyediakan $pdo + memuat .env

try {
    $request = Request::capture();

    $users  = new UserRepository($pdo);
    $jwt    = Jwt::fromEnv();
    $auth   = new AuthController($users, $jwt);
    $todos  = new TodoController(new TodoRepository($pdo));
    $upload = new UploadController();

    $router = new Router();

    // --- Auth (publik, kecuali /me)
    $router->add('POST', '/auth/register', [$auth, 'register'], auth: false);
    $router->add('POST', '/auth/login',    [$auth, 'login'],    auth: false);
    $router->add('GET',  '/auth/me',       [$auth, 'me']);

    // --- Todos (resource REST)
    $router->add('GET',    '/todos',      [$todos, 'index']);
    $router->add('POST',   '/todos',      [$todos, 'store']);
    $router->add('GET',    '/todos/{id}', [$todos, 'show']);
    $router->add('PUT',    '/todos/{id}', [$todos, 'replace']);
    $router->add('PATCH',  '/todos/{id}', [$todos, 'modify']);
    $router->add('DELETE', '/todos/{id}', [$todos, 'destroy']);

    // --- Upload gambar
    $router->add('POST', '/uploads', [$upload, 'store']);

    $route = $router->match($request);
    $user  = [];

    if ($route['auth']) {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new ApiException(
                401,
                'Autentikasi diperlukan. Sertakan header Authorization: Bearer <token>.',
                headers: ['WWW-Authenticate' => 'Bearer'],
            );
        }

        $user = $users->findById($jwt->verify($token));

        if ($user === null) {
            throw new ApiException(401, 'User untuk token ini sudah tidak ada.', headers: ['WWW-Authenticate' => 'Bearer']);
        }
    }

    ($route['handler'])($request, $route['params'], $user);
} catch (ApiException $e) {
    Response::error($e->status, $e->getMessage(), $e->errors, $e->headers);
} catch (Throwable $e) {
    error_log('API error: ' . $e::class . ': ' . $e->getMessage());

    Response::error(500, 'Terjadi kesalahan pada server.');
}
