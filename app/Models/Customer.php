<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可するカラムです。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'postal_code',
        'address',
        'memo',
        'business_card_front_path',
        'business_card_back_path',
    ];

    /**
     * 顧客を所有するユーザーです。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 顧客に紐づく案件一覧です。
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}