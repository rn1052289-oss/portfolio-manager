<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CustomerBusinessCardService
{
    /**
     * 名刺画像を保存するdiskです。
     */
    private const DISK = 'public';

    /**
     * public disk内の名刺画像保存先です。
     */
    private const DIRECTORY = 'customers/business-cards';

    /**
     * 名刺画像を保存し、DB保存用の相対パスを返します。
     */
    public function store(UploadedFile $image): string
    {
        $path = $image->store(
            self::DIRECTORY,
            self::DISK
        );

        if ($path === false) {
            throw new RuntimeException(
                '名刺画像の保存に失敗しました。'
            );
        }

        return $path;
    }

    /**
     * 管理対象の名刺画像を削除します。
     */
    public function delete(?string $path): bool
    {
        if ($path === null || $path === '') {
            return true;
        }

        if (!$this->isManagedPath($path)) {
            Log::warning(
                '管理対象外の名刺画像パスの削除を拒否しました。',
                [
                    'disk' => self::DISK,
                    'path' => $path,
                ]
            );

            return false;
        }

        $disk = Storage::disk(self::DISK);

        /*
         * DBにパスが残っていても実ファイルが存在しない場合は、
         * すでに削除済みとみなします。
         */
        if (!$disk->exists($path)) {
            return true;
        }

        $deleted = $disk->delete($path);

        if (!$deleted) {
            Log::warning(
                '名刺画像の削除に失敗しました。',
                [
                    'disk' => self::DISK,
                    'path' => $path,
                ]
            );
        }

        return $deleted;
    }

    /**
     * 複数の名刺画像を削除します。
     *
     * DB登録失敗時のcleanupなどで使用します。
     *
     * @param array<int, string|null> $paths
     */
    public function deleteMany(array $paths): void
    {
        $uniquePaths = array_unique(
            array_filter(
                $paths,
                fn($path) => is_string($path) && $path !== ''
            )
        );

        foreach ($uniquePaths as $path) {
            $this->delete($path);
        }
    }

    /**
     * CustomerBusinessCardServiceが管理するパスか確認します。
     */
    private function isManagedPath(string $path): bool
    {
        return preg_match(
            '#\Acustomers/business-cards/[A-Za-z0-9][A-Za-z0-9._-]*\z#',
            $path
        ) === 1;
    }
}