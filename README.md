---
### 顧客管理

顧客情報と名刺画像を一元管理できる機能を実装しています。

- 顧客一覧
- 顧客登録
- 顧客詳細
- 顧客編集
- 顧客削除
- 担当者名
- メールアドレス
- 電話番号
- 郵便番号
- 住所
- メモ管理
- 名刺画像（表面・裏面）
- ログインユーザーごとの顧客管理
- 同一ユーザー内の顧客名重複防止

CustomerPolicyとRoute Model Bindingを利用し、他ユーザーの顧客を閲覧・編集・削除できないよう制御しています。
---

### 名刺画像管理

顧客ごとに名刺画像（表面・裏面）を登録できます。

対応形式

- JPG
- JPEG
- PNG
- WebP
- 最大5MB
- SVG非対応

画像はLaravel Storageの`public` diskへ保存し、DBには相対パスのみ保存しています。

```text
DB
customers/business-cards/xxxxxxxx.png

実ファイル
storage/app/public/customers/business-cards/
```

実装内容

- 表面・裏面を個別アップロード
- 片面のみ登録対応
- 編集時の片面差し替え
- 両面同時差し替え
- 未選択画像は維持
- 画像未登録時のfallback表示
- DBパスあり・実ファイルなしでもfallback表示
- 顧客削除時の画像自動削除
