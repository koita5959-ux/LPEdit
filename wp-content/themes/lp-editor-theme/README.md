# LP Editor Theme

WordPressベースのランディングページ自動生成システム

## 概要

メールアドレスを入力するだけでLPを自動生成し、編集・公開できるシステム。

## 主要機能

### LP作成・編集
- メールアドレス認証によるLP作成
- ワンタイムトークンによる編集URL発行
- リアルタイムプレビュー付きエディタ
- 画像アップロード（メディアライブラリ連携）
  - JPEG/PNG/GIF/WebP のみ、5MB以下

### お問い合わせフォーム
- 動的フォーム項目（ACFで管理）
- LP制作者へのメール通知
- フォーム送信者への自動返信メール
- 送信成功/エラーメッセージ表示

### メール送信
- WP Mail SMTPプラグイン経由
- さくらサーバーSMTP使用
- 送信元: lpeditor@media-house.jp

## ファイル構成

```
lp-editor-theme/
├── functions.php          # エントリポイント（inc読込）
├── inc/
│   ├── setup.php          # CPT/アセット/管理画面設定
│   ├── helpers.php        # 共通ヘルパー
│   ├── template-logic.php # templateページ生成/初期データ
│   ├── api.php            # REST API（preview/create/update/delete等）
│   └── contact.php        # 公開LPフォーム送信処理
├── page-editor.php        # LP編集画面
├── page-select.php        # 新規作成/既存編集 入口画面
├── single-lp.php          # 公開LP表示テンプレート
├── templates/
│   ├── public.php         # 公開用HTMLテンプレート
│   └── preview.php        # プレビュー用HTMLテンプレート
└── assets/
    └── images/            # デフォルト画像
```

## 設定

### WP Mail SMTP設定
- メーラー: Other SMTP（さくらサーバー）
- 送信元メールアドレス: lpeditor@media-house.jp
- 送信元メールアドレスを強制使用: ON

### DNS設定（media-house.jp）
- SPFレコード: 有効
- DKIM: 有効（セレクタ: rs20260218）
- DMARC: 有効（ポリシー: none）

## 開発メモ

### 本番切替
- 手順は `TEST_CHECKLIST.md` を使用
- 結果記録は `TEST_REPORT_TEMPLATE.md` を使用

### セキュリティ方針（2026-02-23 時点）
- 公開API（`preview/create/upload/request-edit-url`）は公開Nonce検証を必須化
- `update/delete` API は編集トークン必須（本番）
- ローカル開発環境のみトークン検証を緩和
- `editor` URL解決は `lp_editor_get_editor_url()` に統一
- `request-edit-url` は `IP + メールアドレス` 単位で60秒レート制限
- `create` は `IP + メールアドレス` 単位で30秒レート制限
- `upload` は `IP` 単位で3秒レート制限
- `request-edit-url` のUIは429時に待機秒を表示し、リロード後もクールダウン状態を維持
- APIのエラー応答形式を統一し、失敗時はサーバーログ（`LP_EDITOR_ENABLE_LOG`）に記録
- レート制限/アップロード制約は `functions.php` の定数で調整可能

### フォーム送信の流れ
1. `template_redirect` フックで `lp_editor_handle_contact_form()` が処理
2. `owner_email`（LP作成時のメールアドレス）宛にメール送信
3. フォーム入力者にも自動返信メール送信
4. 成功/エラーは `transient` で一時保存し、リダイレクト後に表示

### LP表示仕様メモ（2026-02-23 更新）
- ヒーロー高さは `84vh`（`templates/preview.php` / `templates/public.php`）
- 固定CTA（`#bottom-cta`）は「ヒーロー通過後」に表示
- お問い合わせ下の旧電話案内ブロックは撤去し、固定CTAを同位置に配置
- 固定電話CTA文言は `電話相談` を標準値として扱う

### メール送信設定
- FromヘッダーはWP Mail SMTPの設定に委任
- Reply-Toにフォーム入力者のメールアドレスを設定
