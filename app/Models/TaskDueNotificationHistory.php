<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDueNotificationHistory extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | 通知種別
    |--------------------------------------------------------------------------
    */

    public const TYPE_THREE_DAYS_BEFORE = 'three_days_before';

    public const TYPE_ONE_DAY_BEFORE = 'one_day_before';

    public const TYPE_DUE_TODAY = 'due_today';

    public const TYPE_OVERDUE = 'overdue';

    /*
    |--------------------------------------------------------------------------
    | 送信状態
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /*
    |--------------------------------------------------------------------------
    | 一括代入を許可するカラム
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'task_id',
        'user_id',
        'due_date',
        'notification_type',
        'recipient_email',
        'status',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    /*
    |--------------------------------------------------------------------------
    | 型変換
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | リレーション
    |--------------------------------------------------------------------------
    */

    /**
     * 通知対象のタスクです。
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * 通知先のユーザーです。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}