<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectCsvExportService
{
    /**
     * CSVに出力する案件ステータスの日本語表示です。
     *
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        'not_started' => '未着手',
        'in_progress' => '進行中',
        'completed' => '完了',
        'paused' => '保留',
    ];

    /**
     * CSVの先頭に付与するUTF-8 BOMです。
     *
     * ExcelでUTF-8の日本語CSVを開いた際の
     * 文字化けを防ぎやすくします。
     */
    private const UTF8_BOM = "\xEF\xBB\xBF";

    public function __construct(
        private readonly ProjectSearchService $projectSearchService
    ) {
    }

    /**
     * 案件CSVをストリーミング形式でダウンロードします。
     *
     * @param  array<string, mixed>  $filters
     */
    public function download(
        User $user,
        array $filters = []
    ): StreamedResponse {
        $fileName = $this->makeFileName();

        return response()->streamDownload(
            function () use ($user, $filters): void {
                $handle = fopen('php://output', 'w');

                if ($handle === false) {
                    throw new RuntimeException(
                        'CSV出力ストリームを開けませんでした。'
                    );
                }

                try {
                    $this->writeBom($handle);
                    $this->writeHeader($handle);

                    $query = $this->makeExportQuery(
                        $user,
                        $filters
                    );

                    foreach ($query->lazy(500) as $project) {
                        $this->writeProjectRow(
                            $handle,
                            $project
                        );
                    }
                } finally {
                    fclose($handle);
                }
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }

    /**
     * CSV出力用の案件クエリを作成します。
     *
     * 一覧画面と同じ検索条件をProjectSearchServiceから取得し、
     * CSVで必要なリレーションと件数を追加します。
     *
     * @param  array<string, mixed>  $filters
     */
    private function makeExportQuery(
        User $user,
        array $filters
    ): Builder {
        return $this->projectSearchService
            ->build($user, $filters)
            ->with([
                'customer',
                'category',
                'tags' => fn($query) => $query
                    ->orderBy('name'),
            ])
            ->withCount([
                'tasks' => fn($query) => $query
                    ->where('user_id', $user->id),
            ]);
    }

    /**
     * UTF-8 BOMを書き込みます。
     *
     * @param  resource  $handle
     */
    private function writeBom($handle): void
    {
        fwrite(
            $handle,
            self::UTF8_BOM
        );
    }

    /**
     * CSVの見出し行を書き込みます。
     *
     * @param  resource  $handle
     */
    private function writeHeader($handle): void
    {
        fputcsv(
            $handle,
            [
                '案件ID',
                '案件コード',
                '案件名',
                'クライアント名',
                '顧客名',
                'カテゴリ',
                'タグ',
                'ステータス',
                '予算',
                '開始日',
                '納期',
                'タスク数',
                '案件説明',
                '登録日時',
                '更新日時',
            ]
        );
    }

    /**
     * 案件1件分をCSVへ書き込みます。
     *
     * @param  resource  $handle
     */
    private function writeProjectRow(
        $handle,
        Project $project
    ): void {
        fputcsv(
            $handle,
            [
                $project->id,
                $this->makeProjectCode($project),
                $this->sanitizeCsvValue($project->title),
                $this->sanitizeCsvValue($project->client_name),
                $this->sanitizeCsvValue(
                    $project->customer?->name ?? '未設定'
                ),
                $this->sanitizeCsvValue(
                    $project->category?->name ?? '未分類'
                ),
                $this->sanitizeCsvValue(
                    $this->makeTagText($project)
                ),
                self::STATUS_LABELS[$project->status]
                ?? $this->sanitizeCsvValue($project->status),
                $project->budget,
                $project->start_date?->format('Y/m/d'),
                $project->deadline?->format('Y/m/d'),
                $project->tasks_count,
                $this->sanitizeCsvValue($project->description),
                $project->created_at?->format('Y/m/d H:i'),
                $project->updated_at?->format('Y/m/d H:i'),
            ]
        );
    }

    /**
     * 案件コードを生成します。
     */
    private function makeProjectCode(Project $project): string
    {
        return 'WEB-' . str_pad(
            (string) $project->id,
            4,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * 複数タグを1つの文字列へまとめます。
     */
    private function makeTagText(Project $project): string
    {
        if ($project->tags->isEmpty()) {
            return '未設定';
        }

        return $project->tags
            ->pluck('name')
            ->implode(' / ');
    }

    /**
     * CSVインジェクション対策を行います。
     *
     * Excelなどが数式として解釈する可能性のある
     * 先頭文字を持つ値には、先頭へシングルクォートを付けます。
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
     */
    private function makeFileName(): string
    {
        return sprintf(
            'projects_%s.csv',
            now()->format('Y-m-d')
        );
    }
}