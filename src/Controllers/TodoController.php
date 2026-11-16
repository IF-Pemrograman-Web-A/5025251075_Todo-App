<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\ApiException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\TodoRepository;
use App\Validation\TodoValidator;

/**
 * CRUD /api/todos. Setiap handler menerima ($request, $params, $user)
 * dan $user adalah pemilik token JWT yang sedang login.
 */
final class TodoController
{
    public function __construct(private readonly TodoRepository $todos)
    {
    }

    /** GET /api/todos?visibility=&completed=&q=  -> 200 */
    public function index(Request $request, array $params, array $user): never
    {
        $filters = [];
        $errors  = [];
        $query   = $request->query;

        if (isset($query['visibility'])) {
            if (in_array($query['visibility'], ['personal', 'shared'], true)) {
                $filters['visibility'] = $query['visibility'];
            } else {
                $errors['visibility'] = 'Visibility harus personal atau shared.';
            }
        }

        if (isset($query['completed'])) {
            $bool = is_string($query['completed'])
                ? filter_var($query['completed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : null;

            if ($bool === null) {
                $errors['completed'] = 'completed harus true atau false.';
            } else {
                $filters['completed'] = $bool;
            }
        }

        if (isset($query['q']) && is_string($query['q'])) {
            $filters['q'] = trim($query['q']);
        }

        if ($errors !== []) {
            throw new ApiException(422, 'Parameter query tidak valid.', $errors);
        }

        $rows = $this->todos->listByOwner((int) $user['id'], $filters);

        Response::success($rows);
    }

    /** GET /api/todos/{id} -> 200 | 403 | 404 */
    public function show(Request $request, array $params, array $user): never
    {
        $todo = $this->findOrFail($params['id']);

        // Todo "shared" milik orang lain boleh dibaca, tetapi tidak boleh diubah
        if ((int) $todo['owner_id'] !== (int) $user['id'] && $todo['visibility'] !== 'shared') {
            throw new ApiException(403, 'Kamu tidak punya akses ke todo ini.');
        }

        Response::success($todo);
    }

    /** POST /api/todos -> 201 + header Location | 422 */
    public function store(Request $request, array $params, array $user): never
    {
        $data = TodoValidator::full($request->json());
        $id   = $this->todos->create((int) $user['id'], $data);

        Response::success(
            $this->todos->find($id),
            201,
            'Todo berhasil ditambahkan.',
            ['Location' => "api/todos/{$id}"],
        );
    }

    /** PUT /api/todos/{id} -> ganti seluruh resource. 200 | 403 | 404 | 422 */
    public function replace(Request $request, array $params, array $user): never
    {
        $todo = $this->findOwnedOrFail($params['id'], $user);
        $data = TodoValidator::full($request->json());

        $this->todos->update((int) $todo['id'], $data);

        Response::success($this->todos->find((int) $todo['id']), 200, 'Todo berhasil diperbarui.');
    }

    /** PATCH /api/todos/{id} -> ubah sebagian field. 200 | 403 | 404 | 422 */
    public function modify(Request $request, array $params, array $user): never
    {
        $todo = $this->findOwnedOrFail($params['id'], $user);
        $data = TodoValidator::partial($request->json());

        $this->todos->update((int) $todo['id'], $data);

        Response::success($this->todos->find((int) $todo['id']), 200, 'Todo berhasil diperbarui.');
    }

    /** DELETE /api/todos/{id} -> 204 | 403 | 404 */
    public function destroy(Request $request, array $params, array $user): never
    {
        $todo = $this->findOwnedOrFail($params['id'], $user);

        $this->todos->delete((int) $todo['id']);

        Response::noContent();
    }

    /** @return array<string,mixed> 400 bila id bukan angka positif, 404 bila tidak ada */
    private function findOrFail(string $rawId): array
    {
        $id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            throw new ApiException(400, 'ID todo harus berupa angka positif.');
        }

        $todo = $this->todos->find($id);

        if ($todo === null) {
            throw new ApiException(404, 'Todo tidak ditemukan.');
        }

        return $todo;
    }

    /** Sama seperti findOrFail, tetapi 403 bila todo milik user lain. */
    private function findOwnedOrFail(string $rawId, array $user): array
    {
        $todo = $this->findOrFail($rawId);

        if ((int) $todo['owner_id'] !== (int) $user['id']) {
            throw new ApiException(403, 'Kamu tidak berhak mengubah todo milik pengguna lain.');
        }

        return $todo;
    }
}
