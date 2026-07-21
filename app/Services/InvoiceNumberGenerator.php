<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InvoiceNumberSequence;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use LogicException;
use OverflowException;

final class InvoiceNumberGenerator
{
    /**
     * 請求書番号の接頭辞です。
     */
    private const PREFIX = 'INV';

    /**
     * 1か月に発行可能な最大連番です。
     *
     * 請求書番号の末尾を4桁で維持するため、
     * 0001から9999までを許可します。
     */
    private const MAX_SEQUENCE_NUMBER = 9999;

    /**
     * ユーザー・発行年月単位で、
     * 次の請求書番号を安全に発行します。
     *
     * この処理は、請求書保存と同じDBトランザクション内で
     * 呼び出す必要があります。
     *
     * 形式：
     * INV-202607-0001
     */
    public function generate(
        int $userId,
        CarbonInterface $issuedAt
    ): string {
        $this->ensureTransactionIsActive();

        $period = $issuedAt->format('Ym');

        /*
         * 対象年月の連番管理行がまだ存在しない場合でも、
         * 同じユーザーによる同時採番を直列化します。
         */
        User::query()
            ->lockForUpdate()
            ->findOrFail($userId);

        $sequence = InvoiceNumberSequence::query()
            ->where('user_id', $userId)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            $sequence = InvoiceNumberSequence::query()
                ->create([
                    'user_id' => $userId,
                    'period' => $period,
                    'last_number' => 0,
                ]);
        }

        $nextNumber = $sequence->last_number + 1;

        if ($nextNumber > self::MAX_SEQUENCE_NUMBER) {
            throw new OverflowException(
                sprintf(
                    '%s年%s月の請求書番号が上限に達しています。',
                    $issuedAt->format('Y'),
                    $issuedAt->format('m')
                )
            );
        }

        $sequence->last_number = $nextNumber;
        $sequence->save();

        return sprintf(
            '%s-%s-%04d',
            self::PREFIX,
            $period,
            $nextNumber
        );
    }

    /**
     * 請求書保存と連番更新を同時に確定させるため、
     * トランザクション外からの呼び出しを禁止します。
     */
    private function ensureTransactionIsActive(): void
    {
        if (DB::transactionLevel() > 0) {
            return;
        }

        throw new LogicException(
            '請求書番号の生成はDBトランザクション内で実行してください。'
        );
    }
}
