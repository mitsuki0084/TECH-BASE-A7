# 旅行プラン作成・共有ツール

## セットアップ手順
1. MySQLにて `sql/schema.sql` を実行し、データベースを構築してください。
2. `config/db.php` の接続情報（ホスト名、ユーザー名、パスワード）を自身のローカル環境に合わせて変更してください。
3. ローカルサーバー（XAMPP/MAMP、または `php -S localhost:8000`）を起動し、ブラウザでアクセスして動作を確認します。

## 開発ルール
- 各自の担当機能はブランチを作成して作業を行ってください（例: `feature/login`）。
- 共通ファイル（`config/db.php`, `includes/header.php`, `includes/footer.php`）を変更する場合は、事前にチーム内で周知してください。