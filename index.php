<?php
declare(strict_types=1);
global $pdo;
require __DIR__.'/db.php';

function generateRandomString(int $length = 6): string{
    $chars =
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOQPRSTUVWXYZ';
    $maxindex = strlen($chars) - 1;
    $result = '';
    for ($i = 0; $i < $length; $i++){
        $result .= $chars[random_int(0, $maxindex)];
    }
    return $result;
}

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$code = trim($url,'/');

if($code !== '' && $code !== basename(__FILE__)){
    $stmt = $pdo->prepare('SELECT long_url FROM urls WHERE code = ?');
    $stmt->execute([$code]);
    $longUrl = $stmt->fetchColumn();
    if($longUrl !== false){
        header('Location: '.$longUrl, true, 302);
        exit;
    }
    http_response_code(404);
    echo '<h1>404 Not Found</h1><p>Код ссылки не найден</p>';
    exit;
}

$error = '';
$shortUrl = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $longUrl = trim((string)($_POST['long_url']??''));
    if (filter_var($longUrl, FILTER_VALIDATE_URL)) {
        do {
            $newCode = generateRandomString(6);
            $check = $pdo->prepare('SELECT 1 FROM urls WHERE code = ?');
            $check->execute([$newCode]);
            $exists = (bool)$check->fetchColumn();
        }while ($exists);

        $insert = $pdo->prepare('INSERT INTO urls (code, long_url) VALUES (?,?)');
        $insert->execute([$newCode, $longUrl]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https':'http';
        $host = $_SERVER['HTTP_HOST'];
        $shortUrl = sprintf('%s://%s/%s', $scheme, $host, $newCode);
    } else {
        $error = 'Неверный URL. Пожалуйста, введите корректный адрес';
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сокращатель ссылок</title>
</head>
<body>
    <h1>Сократить ссылку</h1>
    <form action="index.php" method="post">
        <label for="long_url">Оригинальная ссылка</label><br>
        <input type="url"
               id="long_url"
               name="long_url"
               placeholder="https://example.com/long/link"
               required
               style="width: 400px;"
               value="<?= htmlspecialchars($_POST['long_url']??'', ENT_QUOTES) ?>"
        ><br><br>
        <button type="submit"
                style="background-color:deepskyblue;
                color:white;
                border-color: darkgrey";
        >Сократить</button>
    </form>
    <?php if ($error):?>
      <p style="color:red;"><?=htmlspecialchars($error, ENT_QUOTES) ?></p>
    <?php elseif ($shortUrl):?>
      <p>Короткая ссылка:
        <a href="<?=htmlspecialchars($shortUrl, ENT_QUOTES) ?>" target="_blank">
            <?=htmlspecialchars($shortUrl, ENT_QUOTES) ?>
        </a>
      </p>
    <?php endif; ?>
</body>
</html>