<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerCsvExportService
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
     * ログインユーザーが所有する顧客をCSVでダウンロードします。
     */
    public function download(User $user): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($user): void {
                $handle = fopen('php://output', 'w');

                if ($handle === false) {
                    throw new RuntimeException(
                        'CSV出力ストリームを開けませんでした。'
                    );
                }

                try {
                    $this->writeBom($handle);
                    $this->writeHeader($handle);

                    /*
                     * 顧客データを一度にすべて読み込まず、
                     * 500件ずつ取得してCSVへ書き込みます。
                     */
                    foreach (
                        $this->makeExportQuery($user)->lazy(500)
                        as $customer
                    ) {
                        $this->writeCustomerRow(
                            $handle,
                            $customer
                        );
                    }
                } finally {
                    fclose($handle);
                }
            },
            $this->makeFileName(),
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store, no-cache',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * CSV出力用の顧客クエリを作成します。
     *
     * ログインユーザーが所有する顧客だけを対象にし、
     * 顧客一覧画面と同じ更新日時・IDの降順で並べます。
     */
    private function makeExportQuery(User $user): Builder
    {
        return $user
            ->customers()
            ->getQuery()
            ->select([
                'customers.id',
                'customers.name',
                'customers.contact_name',
                'customers.email',
                'customers.phone',
                'customers.postal_code',
                'customers.address',
                'customers.memo',
                'customers.created_at',
                'customers.updated_at',
            ])
            ->withCount([
                'projects' => fn(Builder $query) => $query
                    ->where('user_id', $user->id),
            ])
            ->orderByDesc('customers.updated_at')
            ->orderByDesc('customers.id');
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
                '顧客ID',
                '顧客コード',
                '顧客名',
                '担当者',
                'メールアドレス',
                '電話番号',
                '郵便番号',
                '住所',
                '案件数',
                'メモ',
                '登録日時',
                '更新日時',
            ]
        );
    }

    /**
     * 顧客1件分をCSVへ書き込みます。
     *
     * @param  resource  $handle
     */
    private function writeCustomerRow(
        $handle,
        Customer $customer
    ): void {
        $this->writeCsvRow(
            $handle,
            [
                $customer->id,

                $this->makeCustomerCode(
                    $customer
                ),

                $this->sanitizeCsvValue(
                    $customer->name
                ),

                $this->sanitizeCsvValue(
                    $customer->contact_name
                ),

                $this->sanitizeCsvValue(
                    $customer->email
                ),

                /*
                 * 電話番号と郵便番号は数値ではありません。
                 *
                 * Excelで開いた際に先頭の0が消えないよう、
                 * 文字列として扱わせます。
                 */
                $this->preserveAsExcelText(
                    $customer->phone
                ),

                $this->preserveAsExcelText(
                    $customer->postal_code
                ),

                $this->sanitizeCsvValue(
                    $customer->address
                ),

                (int) $customer->projects_count,

                $this->sanitizeCsvValue(
                    $customer->memo
                ),

                $customer->created_at?->format(
                    'Y/m/d H:i'
                ),

                $customer->updated_at?->format(
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
     * 顧客コードを生成します。
     *
     * 例：CUS-0001
     */
    private function makeCustomerCode(
        Customer $customer
    ): string {
        return 'CUS-' . str_pad(
            (string) $customer->id,
            4,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * 電話番号・郵便番号をExcel上で文字列として保持します。
     *
     * 例：
     * 09012345678
     * ↓
     * '09012345678
     */
    private function preserveAsExcelText(
        mixed $value
    ): string {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        return "'" . $value;
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
         * 空白や改行の後ろに数式記号がある場合も
         * 検出できるようにします。
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
     * 例：customers_2026-07-14.csv
     */
    private function makeFileName(): string
    {
        return sprintf(
            'customers_%s.csv',
            now()->format('Y-m-d')
        );
    }
}