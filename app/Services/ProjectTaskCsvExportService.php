<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectTaskCsvExportService
{
    /**
     * ExcelにUTF-8として認識させるためのBOMです。
     */
    private const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * CSVの区切り文字です。
     */
    private const CSV_DELIMITER = ',';

    /**
     * CSVの囲み文字です。
     */
    private const CSV_ENCLOSURE = '"';

    /**
     * PHP独自のエスケープ処理を無効化します。
     */
    private const CSV_ESCAPE = '';

    /**
     * Excelとの互換性を考慮してCRLFを使用します。
     */
    private const CSV_EOL = "\r\n";

    /**
     * タスクステータスの日本語表示です。
     *
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        Task::STATUS_NOT_STARTED => '未着手',
        Task::STATUS_IN_PROGRESS => '進行中',
        Task::STATUS_COMPLETED => '完了',
    ];

    /**
     * タスク優先度の日本語表示です。
     *
     * @var array<string, string>
     */
    private const PRIORITY_LABELS = [
        Task::PRIORITY_LOW => '低',
        Task::PRIORITY_MEDIUM => '中',
        Task::PRIORITY_HIGH => '高',
    ];

    /**
     * 指定案件に紐づくタスクをCSVでダウンロードします。
     */
    public function download(
        User $user,
        Project $project
    ): StreamedResponse {
        return response()->streamDownload(
            function () use ($user, $project): void {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                if ($handle === false) {
                    throw new RuntimeException(
                        'CSV出力ストリームを開けませんでした。'
                    );
                }

                try {
                    $this->writeBom($handle);
                    $this->writeHeader($handle);

                    /*
                     * 全タスクを一度にメモリへ読み込まず、
                     * 500件ずつ取得してCSVへ書き込みます。
                     */
                    foreach (
                        $this->makeExportQuery(
                            $user,
                            $project
                        )->lazy(500) as $task
                    ) {
                        $this->writeTaskRow(
                            $handle,
                            $project,
                            $task
                        );
                    }
                } finally {
                    fclose($handle);
                }
            },
            $this->makeFileName($project),
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store, no-cache',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * CSV出力用のタスククエリを作成します。
     *
     * 指定案件に紐づき、ログインユーザーが所有する
     * タスクだけを取得します。
     *
     * タスク一覧画面と同じ並び順を使用します。
     */
    private function makeExportQuery(
        User $user,
        Project $project
    ): Builder {
        return $project
            ->tasks()
            ->getQuery()
            ->select([
                'tasks.id',
                'tasks.project_id',
                'tasks.title',
                'tasks.memo',
                'tasks.status',
                'tasks.priority',
                'tasks.due_date',
                'tasks.completed_at',
                'tasks.sort_order',
                'tasks.created_at',
                'tasks.updated_at',
            ])
            ->where(
                'tasks.user_id',
                $user->id
            )
            ->orderBy(
                'tasks.sort_order'
            )
            ->orderByRaw(
                'tasks.due_date IS NULL'
            )
            ->orderBy(
                'tasks.due_date'
            )
            ->orderByDesc(
                'tasks.created_at'
            )
            ->orderBy(
                'tasks.id'
            );
    }

    /**
     * UTF-8 BOMをCSVの先頭へ書き込みます。
     *
     * @param  resource  $handle
     */
    private function writeBom($handle): void
    {
        $result = fwrite(
            $handle,
            self::UTF8_BOM
        );

        if ($result === false) {
            throw new RuntimeException(
                'CSVへUTF-8 BOMを書き込めませんでした。'
            );
        }
    }

    /**
     * CSVの見出し行を書き込みます。
     *
     * @param  resource  $handle
     */
    private function writeHeader($handle): void
    {
        $this->writeCsvRow(
            $handle,
            [
                'タスクID',
                'タスクコード',
                '案件ID',
                '案件コード',
                '案件名',
                'クライアント名',
                'タスク名',
                'ステータス',
                '優先度',
                '期限',
                '完了日時',
                '表示順',
                'メモ',
                '登録日時',
                '更新日時',
            ]
        );
    }

    /**
     * タスク1件分をCSVへ書き込みます。
     *
     * @param  resource  $handle
     */
    private function writeTaskRow(
        $handle,
        Project $project,
        Task $task
    ): void {
        $this->writeCsvRow(
            $handle,
            [
                $task->id,

                $this->makeTaskCode(
                    $task
                ),

                $project->id,

                $this->makeProjectCode(
                    $project
                ),

                $this->sanitizeCsvValue(
                    $project->title
                ),

                $this->sanitizeCsvValue(
                    $project->client_name
                ),

                $this->sanitizeCsvValue(
                    $task->title
                ),

                $this->makeStatusLabel(
                    $task->status
                ),

                $this->makePriorityLabel(
                    $task->priority
                ),

                $task->due_date?->format(
                    'Y/m/d'
                ),

                $task->completed_at?->format(
                    'Y/m/d H:i'
                ),

                (int) $task->sort_order,

                $this->sanitizeCsvValue(
                    $task->memo
                ),

                $task->created_at?->format(
                    'Y/m/d H:i'
                ),

                $task->updated_at?->format(
                    'Y/m/d H:i'
                ),
            ]
        );
    }

    /**
     * 配列をCSVの1行として書き込みます。
     *
     * @param  resource  $handle
     * @param  array<int, mixed>  $fields
     */
    private function writeCsvRow(
        $handle,
        array $fields
    ): void {
        $result = fputcsv(
            $handle,
            $fields,
            self::CSV_DELIMITER,
            self::CSV_ENCLOSURE,
            self::CSV_ESCAPE,
            self::CSV_EOL
        );

        if ($result === false) {
            throw new RuntimeException(
                'CSVデータを書き込めませんでした。'
            );
        }
    }

    /**
     * タスクコードを生成します。
     *
     * 例：TSK-0001
     */
    private function makeTaskCode(
        Task $task
    ): string {
        return 'TSK-' . str_pad(
            (string) $task->id,
            4,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * 案件コードを生成します。
     *
     * 例：WEB-0001
     */
    private function makeProjectCode(
        Project $project
    ): string {
        return 'WEB-' . str_pad(
            (string) $project->id,
            4,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * ステータスを日本語へ変換します。
     */
    private function makeStatusLabel(
        mixed $status
    ): string {
        if (!is_string($status)) {
            return '';
        }

        return self::STATUS_LABELS[$status]
            ?? $this->sanitizeCsvValue($status);
    }

    /**
     * 優先度を日本語へ変換します。
     */
    private function makePriorityLabel(
        mixed $priority
    ): string {
        if (!is_string($priority)) {
            return '';
        }

        return self::PRIORITY_LABELS[$priority]
            ?? $this->sanitizeCsvValue($priority);
    }

    /**
     * CSVインジェクション対策を行います。
     *
     * Excelが数式として解釈する可能性がある値には、
     * 先頭へシングルクォートを付けます。
     */
    private function sanitizeCsvValue(
        mixed $value
    ): string {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        /*
         * Windows・macOSなどで異なる改行コードを
         * LFへ統一します。
         *
         * セル内改行はfputcsv()が適切に処理します。
         */
        $value = str_replace(
            ["\r\n", "\r"],
            "\n",
            $value
        );

        /*
         * 先頭に空白や改行を置いてから
         * 数式記号を書くパターンも検出します。
         */
        $trimmedValue = ltrim($value);

        if ($trimmedValue === '') {
            return $value;
        }

        $firstCharacter = mb_substr(
            $trimmedValue,
            0,
            1
        );

        if (
            in_array(
                $firstCharacter,
                ['=', '+', '-', '@'],
                true
            )
        ) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * ダウンロードファイル名を生成します。
     *
     * 例：project_12_tasks_2026-07-14.csv
     */
    private function makeFileName(
        Project $project
    ): string {
        return sprintf(
            'project_%d_tasks_%s.csv',
            $project->id,
            now()->format('Y-m-d')
        );
    }
}