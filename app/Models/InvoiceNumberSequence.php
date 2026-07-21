<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InvoiceNumberSequence extends Model
{
    /**
     * 一括代入を許可するカラムです。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'period',
        'last_number',
    ];

    /**
     * 属性を適切なPHP型へ変換します。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'last_number' => 'integer',
    ];

    /**
     * この連番を所有するユーザーです。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
