<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * タスクモデル
 *
 * @property int $id
 * @property int $user_id
 * @property int $project_id
 * @property string $title
 * @property string|null $memo
 * @property string $status
 * @property string $priority
 * @property Carbon|null $due_date
 * @property Carbon|null $completed_at
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Task extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | ステータス
    |--------------------------------------------------------------------------
    */

    public const STATUS_NOT_STARTED = 'not_started';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    /*
    |--------------------------------------------------------------------------
    | 優先度
    |--------------------------------------------------------------------------
    */

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    /*
    |--------------------------------------------------------------------------
    | 一括代入を許可するカラム
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'memo',
        'status',
        'priority',
        'due_date',
        'completed_at',
        'sort_order',
    ];

    /*
    |--------------------------------------------------------------------------
    | 型変換
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | リレーション
    |--------------------------------------------------------------------------
    */

    /**
     * タスクを所有するユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * タスクが所属する案件
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}