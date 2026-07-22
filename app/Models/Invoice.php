<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $issued_at
 * @property Carbon|null $due_date
 * @property Carbon|null $transaction_date
 * @property Carbon|null $sent_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $canceled_at
 */
final class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELED = 'canceled';

    /**
     * 現在の標準税率です。
     */
    public const DEFAULT_TAX_RATE = 10;

    /**
     * 消費税の端数を切り捨てます。
     */
    public const TAX_ROUNDING_FLOOR = 'floor';

    /**
     * 使用可能な請求書ステータスです。
     *
     * @var array<int, string>
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELED,
    ];

    /**
     * 一括代入を許可するカラムです。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'customer_id',
        'invoice_number',
        'public_token',
        'title',
        'description',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'billing_name',
        'billing_contact_name',
        'billing_email',
        'billing_phone',
        'billing_postal_code',
        'billing_address',

        /*
         * 請求書作成・更新時点の発行者情報です。
         */
        'issuer_business_name',
        'issuer_representative_name',
        'issuer_email',
        'issuer_phone',
        'issuer_postal_code',
        'issuer_address',
        'issuer_registration_number',
        'issuer_bank_name',
        'issuer_bank_branch_name',
        'issuer_bank_account_type',
        'issuer_bank_account_number',
        'issuer_bank_account_holder',

        /*
         * 取引日・税率・端数処理方法です。
         */
        'transaction_date',
        'tax_rate',
        'tax_rounding_method',

        'status',
        'issued_at',
        'due_date',
        'sent_at',
        'paid_at',
        'canceled_at',
        'pdf_path',
        'last_sent_to',
        'send_count',
    ];

    /**
     * 属性を適切なPHP型へ変換します。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'tax_rate' => 'integer',

        'issued_at' => 'date',
        'due_date' => 'date',
        'transaction_date' => 'date',

        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'canceled_at' => 'datetime',

        /*
         * 請求書に保存した口座番号も、
         * DBへ平文保存しないよう暗号化します。
         */
        'issuer_bank_account_number' => 'encrypted',

        'send_count' => 'integer',
    ];

    /**
     * この請求書を所有するユーザーです。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この請求書に紐づく案件です。
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * この請求書に紐づく顧客です。
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * この請求書に紐づく決済一覧です。
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * 下書きか判定します。
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * 送信済みか判定します。
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * 入金済みか判定します。
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * 支払期限切れか判定します。
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE;
    }

    /**
     * キャンセル済みか判定します。
     */
    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }
}
