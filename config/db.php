<?php
/**
 * DB接続共通モジュール
 * 概要: PDOを使用したデータベース接続を生成し、$pdoオブジェクトを提供する。
 */

$host    = 'localhost';
$dbname  = 'travel_plan_db';
$charset = 'utf8mb4';
$user    = 'root';
$password = ''; // 開発環境に応じて変更（例: MAMPは'root'、XAMPPは''）

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 例外を発生させる
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // 連想配列でデータを取得
    PDO::ATTR_EMULATE_PREPARES   => false,                 // 静的プレースホルダを使用
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    // 接続失敗時に処理を停止し、エラーメッセージを表示
    exit('データベース接続エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}