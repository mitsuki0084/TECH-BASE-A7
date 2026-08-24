<?php
// filepath: c:\xampp\htdocs\TECH-BASE-A7\weather_test.php

function fetchJson(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        throw new Exception('APIに接続できませんでした。');
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode !== 200) {
        throw new Exception('APIエラーが発生しました。');
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        throw new Exception('APIのデータ形式が不正です。');
    }

    return $data;
}

function weatherText(int $code): string
{
    return match (true) {
        $code === 0 => '快晴',
        in_array($code, [1, 2, 3], true) => '晴れ・曇り',
        in_array($code, [45, 48], true) => '霧',
        in_array($code, [51, 53, 55, 56, 57], true) => '霧雨',
        in_array($code, [61, 63, 65, 66, 67], true) => '雨',
        in_array($code, [71, 73, 75, 77], true) => '雪',
        in_array($code, [80, 81, 82], true) => 'にわか雨',
        in_array($code, [85, 86], true) => 'にわか雪',
        in_array($code, [95, 96, 99], true) => '雷雨',
        default => '不明',
    };
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$place = trim($_GET['place'] ?? '');
$error = '';
$location = null;
$weather = null;

if ($place !== '') {
    try {
        $geocodeUrl = 'https://geocoding-api.open-meteo.com/v1/search?' .
            http_build_query([
                'name' => $place,
                'count' => 1,
                'language' => 'ja',
                'format' => 'json',
            ]);

        $geocode = fetchJson($geocodeUrl);
        $location = $geocode['results'][0] ?? null;

        if (!$location) {
            throw new Exception('入力した地点が見つかりません。');
        }

        $weatherUrl = 'https://api.open-meteo.com/v1/forecast?' .
            http_build_query([
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'current' => 'temperature_2m,weather_code,wind_speed_10m',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                'forecast_days' => 7,
                'timezone' => 'auto',
            ]);

        $weather = fetchJson($weatherUrl);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>天気予報</title>
</head>
<body>
    <h1>天気予報</h1>

    <form method="get">
        <label>
            地点：
            <input
                type="text"
                name="place"
                value="<?= h($place) ?>"
                placeholder="例：札幌市"
                required
            >
        </label>
        <button type="submit">検索</button>
    </form>

    <?php if ($error !== ''): ?>
        <p><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($location && $weather): ?>
        <h2><?= h($location['name']) ?>の天気</h2>

        <h3>現在の天気</h3>
        <p>
            天気：<?= h(weatherText((int)$weather['current']['weather_code'])) ?><br>
            気温：<?= h((string)$weather['current']['temperature_2m']) ?>℃<br>
            風速：<?= h((string)$weather['current']['wind_speed_10m']) ?> km/h
        </p>

        <h3>7日間の天気予報</h3>

        <table border="1" cellpadding="8">
            <tr>
                <th>日付</th>
                <th>天気</th>
                <th>最高気温</th>
                <th>最低気温</th>
            </tr>

            <?php foreach ($weather['daily']['time'] as $index => $date): ?>
                <tr>
                    <td><?= h($date) ?></td>
                    <td>
                        <?= h(weatherText((int)$weather['daily']['weather_code'][$index])) ?>
                    </td>
                    <td>
                        <?= h((string)$weather['daily']['temperature_2m_max'][$index]) ?>℃
                    </td>
                    <td>
                        <?= h((string)$weather['daily']['temperature_2m_min'][$index]) ?>℃
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>