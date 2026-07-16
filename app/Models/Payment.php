<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory;

    public const STATUS_REQUIRES_PAYMENT_METHOD = 'requires_payment_method';

    public const STATUS_REQUIRES_CONFIRMATION = 'requires_confirmation';

    public const STATUS_REQUIRES_ACTION = 'requires_action';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_CANCELED = 'canceled';

    /**
     * 決済確定APIを実行できるStripeステータス。
     *
     * @var array<int, string>
     */
    private const CONFIRMABLE_STATUSES = [
        self::STATUS_REQUIRES_PAYMENT_METHOD,
        self::STATUS_REQUIRES_CONFIRMATION,
    ];

    /**
     * 一括代入を許可するカラムです。
     *
     * user_idはリクエストから直接受け取らず、
     * ログインユーザーのpaymentsリレーション経由で設定します。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'stripe_payment_intent_id',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'description',
        'failure_message',
        'paid_at',
        'canceled_at',
        'metadata',
    ];

    /**
     * 属性を適切なPHP型へ変換します。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * 決済がすでに成功しているか判定する。
     */
    public function isSucceeded(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    /**
     * 決済がキャンセル済みか判定する。
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    /**
     * 現在の状態で決済確定APIを実行できるか判定する。
     */
    public function canBeConfirmed(): bool
    {
        return in_array(
            $this->status,
            self::CONFIRMABLE_STATUSES,
            true
        );
    }

    /**
     * この決済を所有するユーザーです。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この決済に紐づく案件です。
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
