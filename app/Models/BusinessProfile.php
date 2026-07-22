<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BusinessProfile extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可するカラムです。
     *
     * user_idはリクエストから直接受け取らず、
     * 認証ユーザーとのリレーション経由で設定します。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_name',
        'representative_name',
        'email',
        'phone',
        'postal_code',
        'address',
        'invoice_registration_number',
        'bank_name',
        'bank_branch_name',
        'bank_account_type',
        'bank_account_number',
        'bank_account_holder',
    ];

    /**
     * 属性を適切な形式へ変換します。
     *
     * 口座番号はDBへ平文保存せず、
     * Laravelの暗号化機能を使って保存します。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bank_account_number' => 'encrypted',
    ];

    /**
     * この事業者プロフィールを所有するユーザーです。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
