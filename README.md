# ClientWork Manager

フリーランス・小規模事業者向けの案件管理Webアプリです。

案件、顧客、タスク、請求書、決済、期限通知を一元管理し、案件の受注から請求・入金確認までを効率化します。

LaravelのCRUDだけでなく、認証・認可、画像管理、CSV出力、PDF生成、メール送信、Stripe決済、Webhook、API、自動テストまで、実務を想定して実装しています。

---

## 開発目的

フリーランスの業務では、案件情報、顧客情報、タスク、請求書、入金状況が複数のサービスやファイルに分散しやすくなります。

ClientWork Managerでは、次の業務を1つのアプリで管理できます。

```text
顧客登録
  ↓
案件登録
  ↓
タスク・進捗管理
  ↓
請求書作成
  ↓
PDF出力・メール送信
  ↓
顧客がStripeで支払い
  ↓
Webhookで入金状態を自動反映
```

---

## 主な機能

### 認証・プロフィール

- Laravel Fortifyによるログイン・新規登録
- プロフィール・メールアドレス・パスワード変更
- 未ログインユーザーのアクセス制御
- Laravel SanctumによるAPIトークン発行

### ダッシュボード

- 進行中案件数
- 今月の売上見込み
- 未完了タスク数
- 総案件数
- 案件ごとの進捗率
- 本日締切のタスク
- ログインユーザーのデータだけを集計

### 案件管理

- 案件の登録・一覧・詳細・編集・削除
- 案件名・顧客名による検索
- ステータス・カテゴリ・タグによる絞り込み
- ページネーション
- 優先度管理
- 案件画像アップロード
- 顧客・カテゴリ・タグとの関連付け
- タスク件数・進捗率表示
- 一覧行から詳細画面への遷移
- PC・スマートフォン対応

### カテゴリ・タグ管理

- カテゴリの登録・編集・削除
- タグの登録・編集・削除
- 案件への複数タグ設定
- 中間テーブルによる多対多リレーション
- `attach()` / `detach()` / `sync()`
- `whereHas()`による絞り込み

### タスク管理

- タスクの登録・一覧・編集・削除
- ステータス管理
    - 未着手
    - 進行中
    - 完了
- 優先度管理
    - 低
    - 中
    - 高
- 期限・メモ管理
- 完了日時の記録
- SortableJSによるドラッグ＆ドロップ並び替え
- 案件単位の進捗率計算
- 他ユーザーのタスクを更新できない所有権チェック

### 顧客・名刺管理

- 顧客の登録・一覧・詳細・編集・削除
- 会社名・担当者名・メール・電話番号・住所・メモ管理
- 同一ユーザー内での顧客名重複防止
- 案件登録時の顧客自動紐付け
- 名刺画像の表面・裏面を個別管理
- 片面のみの登録・差し替えに対応
- 未選択画像の維持
- 顧客削除時の画像削除
- ファイル欠損時のフォールバック表示

対応画像：

```text
JPG / JPEG / PNG / WebP
最大5MB
SVG非対応
```

画像はLaravel Storageの`public`ディスクへ保存し、DBには相対パスだけを保存します。

```text
DB:
customers/business-cards/xxxxxxxx.webp

保存先:
storage/app/public/customers/business-cards/
```

---

## 請求書管理

- 請求書の作成・一覧・詳細・編集・削除
- 案件・顧客との関連付け
- 請求先情報のスナップショット保存
- 小計・消費税・合計金額のサーバー側計算
- 請求書番号の管理
- 発行日・支払期限・入金日時の管理
- 一覧行・スマートフォンカードから詳細画面へ遷移
- 請求書詳細画面で`paid_at`を表示
- 決済履歴件数の表示

請求書ステータス：

```text
draft      下書き
sent       送信済み
paid       入金済み
overdue    期限超過
canceled   キャンセル
```

削除できるのは、決済履歴が存在しない下書き請求書だけです。

---

## 請求書PDF

- PDFプレビュー
- PDFダウンロード
- 日本語フォント対応
- 請求元・請求先・金額・税額・期限を出力
- PDF生成処理をServiceへ分離
- 管理画面のHTMLとは別のPDF専用Bladeを使用

---

## 請求書メール

- 請求書メール作成画面
- 顧客のメールアドレスへ送信
- PDF添付
- Mailpitによるローカル受信確認
- Controllerからメール・PDF生成処理を分離
- 送信回数・送信日時を記録
- 送信失敗時のエラーハンドリング

---

## 顧客向けオンライン支払い

ログイン不要の公開請求書ページから、顧客がStripeで支払えます。

### 支払いフロー

```text
事業者が請求書を発行
  ↓
顧客が公開支払いページを開く
  ↓
Stripe Payment Elementへカード情報を入力
  ↓
PaymentIntentを確定
  ↓
Stripe Webhookを受信
  ↓
Paymentをsucceededへ更新
  ↓
Invoiceをpaidへ更新
  ↓
管理画面で入金確認
```

### 公開URL

```text
GET  /invoice-payments/{publicToken}
POST /invoice-payments/{publicToken}
GET  /invoice-payments/{publicToken}/result
```

公開URLには数値IDを使用せず、推測困難なUUIDを使用しています。

```text
例:
invoice-payments/d5d42616-e96e-442c-9085-a37fc14783aa
```

### 公開画面の制御

| 請求書状態 | 公開画面       |
| ---------- | -------------- |
| draft      | 404            |
| sent       | 支払い可能     |
| overdue    | 支払い可能     |
| paid       | 支払い済み表示 |
| canceled   | キャンセル表示 |

### セキュリティ対策

- 公開URLにInvoice ID・Payment IDを使用しない
- UUID型`public_token`へunique制約
- PaymentIntentの金額・通貨をブラウザから受け取らない
- Invoiceに保存された金額・通貨を使用
- `public_token`をStripe metadataへ保存しない
- `client_secret`をログへ出力しない
- 公開画面に管理画面への導線を表示しない
- キャッシュ防止ヘッダー
- 検索エンジンのインデックス防止
- 決済開始エンドポイントへレート制限
- ブラウザの完了画面だけでは入金済みにしない

公開画面のレスポンスヘッダー：

```text
Cache-Control: no-store, private, max-age=0
Pragma: no-cache
X-Robots-Tag: noindex, nofollow, noarchive
```

---

## Stripe決済・Webhook

### PaymentIntent

- Stripe Payment Element
- PaymentIntentの作成・取得・確定
- 冪等性キーによる二重作成防止
- 進行中PaymentIntentの再利用
- キャンセル済みPaymentIntentからの再作成
- PaymentとInvoiceの紐付け
- 決済金額・通貨・所有者・案件の整合性確認

### 対応Webhookイベント

```text
payment_intent.succeeded
payment_intent.payment_failed
payment_intent.processing
payment_intent.canceled
```

### Webhook処理

- `Stripe-Signature`の検証
- Webhook payloadの状態をそのまま信用しない
- Stripe APIからPaymentIntentの最新状態を再取得
- Paymentを行ロックして同期
- Invoiceを同じDBトランザクション内で行ロック
- PaymentとInvoiceを同時更新
- 例外発生時は両方をロールバック
- 重複Webhookでも`paid_at`を上書きしない
- 請求書以外の決済は従来どおりPaymentだけ更新

Invoiceを`paid`へ変更できるのは、次の条件をすべて満たす場合だけです。

```text
Payment.status = succeeded
Invoice.status = sent または overdue
Payment.invoice_id = Invoice.id
Payment.user_id = Invoice.user_id
Payment.project_id = Invoice.project_id
Payment.amount = Invoice.total_amount
Payment.currency = Invoice.currency
```

`draft`または`canceled`の請求書へ成功決済が紐づいた場合は、異常状態として検出し、自動更新しません。

---

## 決済API

Laravel SanctumのAPIトークンを使用して、決済処理をAPIから実行できます。

```text
POST  /api/auth/token
POST  /api/payments
PATCH /api/payments/{payment}/confirm
POST  /api/webhooks/stripe
```

主な対策：

- APIトークン認証
- `Idempotency-Key`必須
- Payment所有者のPolicy認可
- Stripe PaymentIntent IDのunique制約
- 冪等性キーのunique制約
- 他ユーザーのPaymentを確定できない
- Stripe APIエラーの適切な変換
- Webhook署名検証

---

## CSVエクスポート

- 案件一覧CSV
- 顧客一覧CSV
- 案件単位のタスクCSV
- ログインユーザーのデータだけを出力
- 検索条件を維持
- `StreamedResponse`によるストリーミング出力
- UTF-8 BOM対応
- `fputcsv()`による安全なCSV生成
- CSVインジェクション対策
- 電話番号・郵便番号の先頭0を維持

---

## タスク期限通知

期限が近い未完了タスクを、案件所有者へメール通知します。

### 通知条件

- 期限7日前
- 期限3日前
- 期限前日
- 期限当日
- 期限超過
- 完了済み・期限なしは対象外
- タスク所有者と案件所有者が一致するデータだけを対象

### 処理構成

```text
Laravel Scheduler
  ↓
Artisan Command
  ↓
TaskDueNotificationService
  ↓
Laravel Notification
  ↓
Mailpit / SMTP
```

### 重複送信防止

通知履歴テーブルへ次の組み合わせを保存します。

```text
task_id + due_date + notification_type
```

通知状態：

```text
pending / sent / failed
```

- 同一タスク・期限・通知種別は重複送信しない
- 期限変更後は新しい通知として送信可能
- `failed`は再試行可能
- 古い`pending`はタイムアウト後に再取得可能
- Scheduler・Service・DB制約の複数段階で重複防止

### 手動実行

```bash
./vendor/bin/sail artisan tasks:send-due-notifications --dry-run
./vendor/bin/sail artisan tasks:send-due-notifications
./vendor/bin/sail artisan tasks:send-due-notifications --date=2030-07-10
```

### Scheduler

毎日、日本時間の午前9時に実行します。

```php
$schedule
    ->command('tasks:send-due-notifications')
    ->dailyAt('09:00')
    ->timezone('Asia/Tokyo')
    ->withoutOverlapping(60);
```

---

## 設計上の工夫

### MVC・責務分離

- Controllerはリクエストとレスポンスの制御を担当
- FormRequestへバリデーションを分離
- Policyへ認可処理を分離
- Service・Actionへ業務ロジックを分離
- Bladeを管理画面・公開画面・PDF・メールで分離

### データベース

- Eloquentリレーションを利用
- `belongsTo`
- `hasMany`
- `belongsToMany`
- 中間テーブル
- 複合unique制約
- 外部キー制約
- `nullOnDelete()`
- トランザクション
- `lockForUpdate()`

### パフォーマンス

- Eager LoadingによるN+1問題対策
- `withCount()`による関連件数取得
- ページネーション
- CSVのストリーミング出力
- Stripe API通信をDBトランザクション外で実行

### セキュリティ

- Fortify認証
- Sanctum API認証
- Policyによる所有者認可
- FormRequestによる入力検証
- CSRF対策
- Webhook署名検証
- UUID公開トークン
- 機密情報をログへ残さない
- 他ユーザーのデータ混入防止
- ファイル形式・容量の検証
- CSVインジェクション対策

---

## レスポンシブデザイン

### PC

- ダークネイビーの固定サイドバー
- 白背景・カードUI
- テーブル形式の一覧
- 薄いシャドウと角丸
- 青系プライマリーボタン

### スマートフォン

- 共通ヘッダー
- 下部固定ナビゲーション
- 一覧をカード形式へ切り替え
- フォーム・検索・ボタンの縦配置
- 430px幅を基準に調整

一覧行・カードは、マウスクリックだけでなく、`Enter`・`Space`キーによる操作にも対応しています。

---

## 技術スタック

| 分類            | 技術                                             |
| --------------- | ------------------------------------------------ |
| Backend         | PHP 8.5 / Laravel 10.x                           |
| Development     | Laravel Sail / Docker Desktop                    |
| Database        | MySQL 8.4                                        |
| Authentication  | Laravel Fortify / Laravel Sanctum                |
| Frontend        | Blade / HTML / CSS / JavaScript                  |
| Build Tool      | Vite 5                                           |
| Payment         | Stripe PaymentIntent / Payment Element / Webhook |
| Mail            | Laravel Mail / Notification / Mailpit            |
| PDF             | Dompdf / IPA日本語フォント                       |
| Drag & Drop     | SortableJS                                       |
| Test            | PHPUnit 10                                       |
| Version Control | Git / GitHub                                     |

---

## セットアップ

### 必要環境

- Docker Desktop
- Git
- Composer

### インストール

```bash
git clone <repository-url>
cd portfolio-manager

composer install
cp .env.example .env

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link

./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

サンプルデータを使用する場合：

```bash
./vendor/bin/sail artisan db:seed
```

アクセス先：

```text
アプリ:
http://localhost

Mailpit:
http://localhost:8025
```

---

## 環境変数

### メール

```env
APP_URL=http://localhost
APP_TIMEZONE=Asia/Tokyo

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=notifications@clientwork-manager.test
MAIL_FROM_NAME="${APP_NAME}"
```

### Stripe

Stripeのテスト用キーを設定します。

```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
```

実際の秘密鍵やWebhookシークレットはGitへ登録しません。

---

## Stripe Webhookのローカル確認

Stripe CLIでローカル環境へWebhookを転送します。

```bash
stripe login
stripe listen --forward-to http://localhost/api/webhooks/stripe
```

表示されたWebhookシークレットを`.env`へ設定します。

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
```

設定を変更した場合：

```bash
./vendor/bin/sail artisan config:clear
```

Stripeの成功用テストカード：

```text
カード番号:
4242 4242 4242 4242

有効期限:
将来の日付

CVC:
任意の3桁
```

実際のカード情報は使用しません。

---

## Schedulerの確認

```bash
./vendor/bin/sail artisan schedule:list
./vendor/bin/sail artisan schedule:work
```

---

## テスト

すべてのテストを実行します。

```bash
./vendor/bin/sail artisan test
```

コード整形：

```bash
./vendor/bin/sail pint <変更したファイル>
```

請求書オンライン決済関連では、次をテストしています。

- 公開請求書ページの表示
- UUID形式不正・存在しないトークンの拒否
- 下書き請求書の非公開
- 支払い済み・キャンセル済みの再決済防止
- セキュリティヘッダー
- Invoice由来の金額・通貨使用
- PaymentIntentの作成・再利用
- Stripeとの金額不一致検出
- Webhook署名検証
- PaymentとInvoiceの同期
- `paid_at`の整合性
- 重複Webhookへの冪等対応
- `processing`・`canceled`でInvoiceを更新しない
- 金額・通貨不一致時のロールバック
- `draft`・`canceled`請求書への誤反映防止
- 請求書以外の既存決済への影響防止

請求書決済関連テスト：

```text
30 tests
206 assertions
```

---

## 主なディレクトリ

```text
app/
├── Actions/                決済作成などのユースケース
├── Console/Commands/       Artisan Command
├── Contracts/              Interface
├── Http/
│   ├── Controllers/        Controller
│   └── Requests/           FormRequest
├── Models/                 Eloquent Model
├── Notifications/          メール通知
├── Policies/               認可
└── Services/               PDF・CSV・通知・Stripe同期処理

resources/
├── css/                    ページ別CSS
├── js/                     Payment Element・並び替え処理
└── views/
    ├── invoice-payments/   顧客向け公開決済画面
    ├── invoices/           請求書管理画面
    └── pdf/                PDF用Blade

tests/
├── Fakes/                  Stripe Fake
├── Feature/                Feature Test
└── Unit/                   Unit Test
```

---

## 実務を意識したポイント

- マルチユーザー環境で他ユーザーのデータを参照・変更できない
- Controllerを肥大化させず責務ごとにService・Actionへ分離
- DB制約とアプリケーション処理の両方で不整合を防止
- Stripeのブラウザ結果ではなくWebhookを正式な入金情報源にする
- PaymentとInvoiceを同一トランザクションで同期
- 冪等性キー・unique制約・行ロックで二重処理を防止
- エラー発生時に中途半端なデータを残さない
- 公開画面へ内部IDや管理機能を露出しない
- 機密情報をログ・Git・画面へ出力しない
- PC・スマートフォン・キーボード操作へ対応
- Feature Testで正常系・異常系・認可境界を検証

---

## ポートフォリオで説明できること

ClientWork Managerでは、単純なCRUDだけでなく、フリーランス業務に必要な一連の流れを実装しました。

```text
認証
案件・顧客管理
タスク・進捗管理
画像アップロード
検索・絞り込み
CSV出力
期限通知
請求書作成
PDF生成
メール送信
Stripe決済
Webhook
API
自動テスト
```

特に、Stripe決済では以下を重視しています。

- Webhookを正式な入金情報源として使用
- PaymentとInvoiceの整合性検証
- DBトランザクションと行ロック
- 重複Webhookへの冪等対応
- 異常データを黙って更新しない設計
- 正常系だけでなく異常系もFeature Testで検証

実務案件で重要になる、保守性・安全性・再利用性・エラー追跡のしやすさを意識して開発しています。

---

## 今後の改善案

- Queueによるメール・通知の非同期処理
- 本番SMTPサービスとの接続
- 通知設定・通知履歴画面
- 請求書メール本文へのオンライン支払いリンク追加
- Stripe返金・一部返金対応
- 決済履歴詳細画面
- CI/CD
- 本番環境へのデプロイ
- 操作ログ・監査ログ
