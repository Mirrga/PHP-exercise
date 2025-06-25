<?php
try {
    $pdo = new PDO('sqlite:'.__DIR__.'/shortener.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('
      CREATE TABLE IF NOT EXISTS urls(
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          code TEXT NOT NULL UNIQUE,
          long_url TEXT NOT NULL,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    ');
} catch(PDOException $e) {
    echo "Ошибка подключения к базе данных:".htmlspecialchars($e->getMessage(), ENT_QUOTES)."\n";
    exit;
}
