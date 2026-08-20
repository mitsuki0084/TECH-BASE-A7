# 旅行プラン作成・共有ツール

旅行プランの作成・共有・検索、および目的地周辺の天気予報を確認できるWebアプリケーションです。
---
## 🛠️ 技術スタック & 動作環境
* **言語 / 技術**: PHP, JavaScript, HTML5, CSS3, SQL
* **サーバー環境**: XAMPP（Apache, MySQL）
* **DB管理ツール**: phpMyAdmin
* **バージョン管理**: Git / GitHub
---
## 🏁 ローカル開発環境の構築手順
### 1. リポジトリのクローン
VSCodeのターミナルを開き、XAMPPの `htdocs` 直下にコードをダウンロードします。
```bash
cd C:\xampp\htdocs
git clone https://github.com/mitsuki0084/TECH-BASE-A7.git
```
### 2. XAMPPの起動
XAMPP コントロールパネルを開き、**「Apache」** と **「MySQL」** の **Start** ボタンを押します（緑色になれば成功）。

### 3. データベースのセットアップ
1. ブラウザで `http://localhost/phpmyadmin/` にアクセスします。
2. 上部メニューの **「インポート」** をクリックします。
3. `C:\xampp\htdocs\TECH-BASE-A7\sql\schema.sql` ファイルを選択し、画面下の **「実行」** を押します。
4. 左側に `travel_plan_db` が作成されていれば完了です。

### 4. 動作確認
ブラウザで以下のURLにアクセスし、「旅プランナー」の画面が表示されるか確認します。
```text
http://localhost/TECH-BASE-A7/index.php

## 📐 開発時の共通ルール・統一変数
プログラム結合時のエラーを防ぐため、以下の命名規則を守って実装してください。
### セッション変数（ログイン状態の保持）

* `$_SESSION['user_id']` : ログインユーザーのID
* `$_SESSION['user_name']` : ログインユーザーの表示名
* `$_SESSION['is_admin']` : 管理者権限フラグ（`0`: 一般, `1`: 管理者）

### HTML要素ID（API連携用）
* `plan_detail.php` 内の目的地表示要素には **`id="destination-name"`** を付与する。
* 天気情報の表示エリアには **`id="weather-info"`** を付与する。
---
## 🔄 基本的なGitワークフロー

1. **作業前に最新の `main` を取り込む**
```bash
git checkout main
git pull origin main
```
2. **作業用ブランチの作成**
```bash
git checkout -b feature/担当機能-自分の名前
```
3. **作業内容の保存・送信**
```bash
git add .
git commit -m "ログイン画面のフォームバリデーション追加"
git push -u origin feature/担当機能-自分の名前
```
4. **GitHub上で Pull Request（PR）を作成**
* GitHub上でPRを作成し、リーダー（みつき）に報告してマージを依頼します。
* **※ `main` ブランチへの直接コミット・プッシュは厳禁です。**
---
## 🆘 トラブル対処（作業の巻き戻し）

作業中にコードが崩れ、最後にコミットした状態に戻したい場合：

```bash
git restore .

```
