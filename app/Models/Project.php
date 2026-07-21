<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $start_date
 * @property Carbon|null $deadline
 */
class Project extends Model
{
    use HasFactory;

    /**
     * 案件優先度です。
     */
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    /**
     * 使用可能な案件優先度です。
     *
     * @var array<int, string>
     */
    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
    ];

    /**
     * 一括代入を許可するカラムです。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'category_id',
        'title',
        'client_name',
        'description',
        'cover_image_path',
        'status',
        'priority',
        'budget',
        'start_date',
        'deadline',
    ];

    /**
     * 属性を変換する設定です。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
    ];

    /**
     * 案件を所有するユーザーです。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 案件に紐づく顧客です。
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 案件に紐づくカテゴリです。
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 案件に紐づくタグ一覧です。
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }

    /**
     * 案件に紐づくタスク一覧です。
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * 案件に紐づく決済履歴一覧です。
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * 案件に紐づく請求書一覧です。
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
