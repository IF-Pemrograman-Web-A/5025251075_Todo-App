<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\ApiException;
use App\Http\Request;
use App\Http\Response;

/** POST /api/uploads  body: {"image": "data:image/jpeg;base64,..."} */
final class UploadController
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    public function store(Request $request, array $params, array $user): never
    {
        $image = $request->json()['image'] ?? '';

        if (!is_string($image) || !preg_match('#^data:image/jpeg;base64,#', $image)) {
            throw new ApiException(422, 'Format gambar tidak valid.', [
                'image' => 'Gunakan data URL image/jpeg;base64.',
            ]);
        }

        $binary = base64_decode(substr($image, strpos($image, ',') + 1), true);

        if ($binary === false) {
            throw new ApiException(422, 'Gambar tidak valid.', ['image' => 'Base64 rusak.']);
        }

        if (strlen($binary) > self::MAX_BYTES) {
            throw new ApiException(413, 'Ukuran gambar melebihi 5 MB.');
        }

        $info = @getimagesizefromstring($binary);

        if ($info === false || $info['mime'] !== 'image/jpeg') {
            throw new ApiException(422, 'File harus berupa gambar JPEG.', ['image' => 'Bukan gambar JPEG.']);
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Folder upload tidak dapat dibuat.');
        }

        $name = bin2hex(random_bytes(16)) . '.jpg';

        if (file_put_contents("{$dir}/{$name}", $binary) === false) {
            throw new \RuntimeException('Gambar gagal disimpan.');
        }

        Response::success(
            ['image_path' => "uploads/{$name}"],
            201,
            'Gambar berhasil diunggah.',
            ['Location' => "uploads/{$name}"],
        );
    }
}
