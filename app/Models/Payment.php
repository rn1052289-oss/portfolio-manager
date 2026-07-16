<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    use HasFactory;

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
