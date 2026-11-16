<?php

declare(strict_types=1);

namespace App\Validation;

use App\Http\ApiException;
use DateTimeImmutable;

/**
 * Validasi input todo. Mengembalikan array field yang sudah bersih,
 * atau melempar 422 dengan detail error per-field.
 */
final class TodoValidator
{
    public const FIELDS = [
        'title', 'description', 'due_date', 'notification_time',
        'image_path', 'is_completed', 'visibility',
    ];

    /**
     * POST (buat baru) & PUT (ganti seluruh resource): title & due_date wajib,
     * field lain yang tidak dikirim dianggap kosong/default.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function full(array $input): array
    {
        return self::validate($input, requireAll: true);
    }

    /**
     * PATCH: hanya field yang dikirim yang divalidasi & diubah.
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function partial(array $input): array
    {
        if (array_intersect(array_keys($input), self::FIELDS) === []) {
            throw new ApiException(422, 'Tidak ada data yang akan diubah.', [
                '_body' => 'Kirim minimal satu field: ' . implode(', ', self::FIELDS) . '.',
            ]);
        }

        return self::validate($input, requireAll: false);
    }

    /** @return array<string,mixed> */
    private static function validate(array $in, bool $requireAll): array
    {
        $out    = [];
        $errors = [];

        // title
        if ($requireAll || array_key_exists('title', $in)) {
            $title = $in['title'] ?? null;

            if (!is_string($title) || trim($title) === '') {
                $errors['title'] = 'Judul todo wajib diisi.';
            } elseif (mb_strlen(trim($title)) > 255) {
                $errors['title'] = 'Judul maksimal 255 karakter.';
            } else {
                $out['title'] = trim($title);
            }
        }

        // description
        if ($requireAll || array_key_exists('description', $in)) {
            $desc = $in['description'] ?? null;

            if ($desc !== null && !is_string($desc)) {
                $errors['description'] = 'Deskripsi harus berupa teks.';
            } elseif (is_string($desc) && mb_strlen($desc) > 5000) {
                $errors['description'] = 'Deskripsi maksimal 5000 karakter.';
            } else {
                $out['description'] = ($desc === null || trim($desc) === '') ? null : trim($desc);
            }
        }

        // due_date
        if ($requireAll || array_key_exists('due_date', $in)) {
            $due = $in['due_date'] ?? null;

            if (!is_string($due) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
                $errors['due_date'] = 'Tanggal harus menggunakan format YYYY-MM-DD.';
            } else {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $due);

                if (!$date || $date->format('Y-m-d') !== $due) {
                    $errors['due_date'] = 'Tanggal tidak valid.';
                } else {
                    $out['due_date'] = $due;
                }
            }
        }

        // notification_time
        if ($requireAll || array_key_exists('notification_time', $in)) {
            $time = $in['notification_time'] ?? null;

            if ($time === null || $time === '') {
                $out['notification_time'] = null;
            } elseif (!is_string($time)) {
                $errors['notification_time'] = 'Waktu pengingat harus menggunakan format YYYY-MM-DD HH:MM:SS.';
            } else {
                $time = str_replace('T', ' ', trim($time));
                $dt   = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $time);

                if (!$dt || $dt->format('Y-m-d H:i:s') !== $time) {
                    $errors['notification_time'] = 'Waktu pengingat harus menggunakan format YYYY-MM-DD HH:MM:SS.';
                } else {
                    $out['notification_time'] = $time;
                }
            }
        }

        // image_path: hanya path hasil POST /api/uploads
        if ($requireAll || array_key_exists('image_path', $in)) {
            $img = $in['image_path'] ?? null;

            if ($img === null || $img === '') {
                $out['image_path'] = null;
            } elseif (!is_string($img) || !preg_match('#^uploads/[a-f0-9]{32}\.jpg$#', $img)) {
                $errors['image_path'] = 'image_path tidak valid. Unggah gambar lewat POST /api/uploads.';
            } else {
                $out['image_path'] = $img;
            }
        }

        // is_completed
        if ($requireAll || array_key_exists('is_completed', $in)) {
            $raw = $in['is_completed'] ?? false;
            $bool = is_bool($raw) || in_array($raw, [0, 1, '0', '1'], true)
                ? filter_var($raw, FILTER_VALIDATE_BOOLEAN)
                : null;

            if ($bool === null) {
                $errors['is_completed'] = 'is_completed harus berupa boolean.';
            } else {
                $out['is_completed'] = $bool;
            }
        }

        // visibility
        if ($requireAll || array_key_exists('visibility', $in)) {
            $vis = $in['visibility'] ?? 'personal';

            if (!in_array($vis, ['personal', 'shared'], true)) {
                $errors['visibility'] = 'Visibility harus personal atau shared.';
            } else {
                $out['visibility'] = $vis;
            }
        }

        if ($errors !== []) {
            throw new ApiException(422, 'Data yang dikirim tidak valid.', $errors);
        }

        return $out;
    }
}
