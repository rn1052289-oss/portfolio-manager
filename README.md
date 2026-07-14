# ClientWork Manager

フリーランス・小規模事業者向けの案件管理Webアプリです。  
案件、顧客、タスク、画像、CSV出力、期限通知を一元管理できます。

## 主な機能

### 認証・権限

- Laravel Fortifyによる認証
- Policyによる所有者認可
- Route Model Binding / Scope Binding
- 他ユーザーの案件・顧客・タスクへのアクセス防止

### ダッシュボード

- 進行中案件数、売上見込み、未完了タスク数、総案件数
- 案件進捗率
- 本日締切のタスク表示

### 案件管理

- 登録・一覧・詳細・編集・削除
- 検索、絞り込み、ページネーション
- カテゴリ・タグ管理
- 案件画像アップロード
- 顧客との紐付け
- タスク件数表示

### タスク管理

- 登録・一覧・編集・削除
- ステータス：未着手・進行中・完了
- 優先度：低・中・高
- 期限・メモ管理
- ドラッグ＆ドロップ並び替え

### 顧客・名刺管理

- 顧客の登録・一覧・詳細・編集・削除
- 担当者名、メール、電話番号、郵便番号、住所、メモ
- 同一ユーザー内の顧客名重複防止
- 名刺画像の表面・裏面を個別管理
- JPG / JPEG / PNG / WebP、最大5MB、SVG非対応
- 片面登録、片面差し替え、未選択画像の維持
- 画像未登録・実ファイル欠損時のfallback表示
- 顧客削除時に名刺画像も自動削除

画像はLaravel Storageの`public` diskへ保存し、DBには相対パスだけを保存します。

```text
DB: customers/business-cards/xxxxxxxx.png
実体: storage/app/public/customers/business-cards/
```

### CSVエクスポート

- 案件一覧CSV
- 顧客一覧CSV
- 案件単位のタスクCSV
- ログインユーザーのデータだけを出力
- 案件検索条件を維持
- UTF-8 BOM、`StreamedResponse`、`fputcsv()`
- CSVインジェクション対策
- 電話番号・郵便番号の先頭0を維持

## タスク期限通知

期限が近い未完了タスクを案件所有者へメール通知します。

### 通知条件

- 期限3日前
- 期限前日
- 期限当日
- 期限超過
- 完了済み・期限なしは対象外
- タスク所有者と案件所有者が一致するデータだけを対象

### 構成

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

- ユーザーごとに対象タスクを1通へ集約
- 案件名、タスク名、期限、優先度、ステータスを表示
- タスク編集・タスク一覧・ダッシュボードへのリンク
- PC・スマホ対応のHTMLメール

### 重複送信防止

通知履歴テーブルに以下を保存し、複合ユニーク制約を設定しています。

```text
task_id + due_date + notification_type
```

通知状態：

```text
pending / sent / failed
```

- 同じタスク・期限・通知種別は再送しない
- 期限変更後は新しい期限として通知可能
- `failed`は再試行可能
- 古い`pending`はタイムアウト後に再取得可能
- Scheduler・Service・DBの3段階で重複を防止

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

```bash
./vendor/bin/sail artisan schedule:list
./vendor/bin/sail artisan schedule:work
```

## 設計上の工夫

- Controllerを肥大化させずServiceへ処理を分離
- FormRequestでバリデーションを分離
- Policyで所有者認可を統一
- Eager Loading / `withCount()`でN+1問題を対策
- Storageにはファイル、DBには相対パスだけを保存
- 画像差し替え・削除時に不要ファイルを削除
- CSV処理を専用Controller・Serviceへ分離
- DBユニーク制約と行ロックで二重処理を抑制
- PHP 8.5対応のPDO設定

## デザイン

- PC：ダークネイビーの固定サイドバー
- スマホ：共通ヘッダー・下部固定ナビ
- 白背景・カードUI・青系プライマリーボタン
- 430px幅を基準にレスポンシブ対応

## 技術スタック

- PHP 8.5
- Laravel 10.x / Laravel Sail
- MySQL 8.4
- Laravel Fortify
- Blade / Vite
- HTML / CSS / JavaScript
- SortableJS
- Docker Desktop / Mailpit
- PHPUnit 10
- Git / GitHub

## セットアップ

### 必要環境

- Docker Desktop
- Git
- Composer

### 起動手順

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

```text
アプリ: http://localhost
Mailpit: http://localhost:8025
```

### メール設定例

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

## テスト

テスト時は開発用DBとは別の`testing`データベースを使用し、メールは`array`ドライバーで外部送信しません。

```bash
./vendor/bin/sail artisan test
```

期限通知では以下をテストしています。

- 通知対象・対象外の判定
- ユーザー別の通知分離
- 重複送信防止
- 期限変更後の再通知
- failed履歴の再試行
- `--dry-run`
- 不正な日付の拒否
- Command経由の通知実行

## 主なディレクトリ

```text
app/Console/Commands/   Artisan Command
app/Http/Controllers/   Controller
app/Http/Requests/      FormRequest
app/Models/             Model
app/Notifications/      メール通知
app/Policies/           認可
app/Services/           検索・CSV・通知処理
resources/views/        Blade
tests/Feature/          Feature Test
```

## 今後の改善案

- Queueによる非同期メール送信
- 本番SMTPサービスとの接続
- 通知設定・通知履歴画面
- API化・Postman検証
- CI/CD・本番デプロイ

## ポートフォリオで説明できる点

CRUDだけでなく、認証・認可、検索、画像管理、CSV、Scheduler、Notification、重複防止、自動テストまで実装しています。  
マルチユーザー環境で他ユーザーのデータが混ざらない設計と、Controller・Service・Policyの責務分離を重視しました。
