<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProjectImageService
{
    /**
     * 案件画像を保存するdisk。
     */
    private const DISK = 'public';

    /**
     * public disk内の案件画像保存ディレクトリ。
     */
    private const DIRECTORY = 'projects/covers';

    /**
     * 案件の代表画像を保存し、DB保存用の相対パスを返す。
     */
    public function store(UploadedFile $image): string
    {
        $path = $image->store(
            self::DIRECTORY,
            self::DISK
        );

        if ($path === false) {
            throw new RuntimeException(
                '案件画像の保存に失敗しました。'
            );
        }

        return $path;
    }

    /**
     * 管理対象の案件画像を削除する。
     *
     * 新規登録失敗時のcleanup、
     * 将来の画像差し替え・案件削除でも再利用する。
     */
    public function delete(?string $path): bool
    {
        if ($path === null || $path === '') {
            return true;
        }

        if (
            !str_starts_with(
                $path,
                self::DIRECTORY . '/'
            )
        ) {
            Log::warning(
                '管理対象外の案件画像パスの削除を拒否しました。',
                [
                    'path' => $path,
                ]
            );

            return false;
        }

        $deleted = Storage::disk(self::DISK)
            ->delete($path);

        if (!$deleted) {
            Log::warning(
                '案件画像の削除に失敗しました。',
                [
                    'disk' => self::DISK,
                    'path' => $path,
                ]
            );
        }

        return $deleted;
    }
}