<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 一括代入を許可するカラムです。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * 配列・JSON変換時に非表示にするカラムです。
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性を変換する設定です。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * ユーザーが所有する案件一覧です。
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * ユーザーが所有する顧客一覧です。
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * ユーザーが所有するカテゴリ一覧です。
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * ユーザーが所有するタグ一覧です。
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * ユーザーが所有するタスク一覧です。
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * ユーザーへ送信したタスク期限通知履歴一覧です。
     */
    public function taskDueNotificationHistories(): HasMany
    {
        return $this->hasMany(TaskDueNotificationHistory::class);
    }

    /**
     * ユーザーが所有する決済履歴一覧です。
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * ユーザーに紐づく請求書一覧です。
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
